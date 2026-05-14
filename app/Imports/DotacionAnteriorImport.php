<?php

namespace App\Imports;

use App\Models\Trabajador;
use App\Models\TrabajadorVinculacion;
use App\Models\CarpetaVerificacion;
use App\Models\CarpetaVerificacionTrabajador;
use App\Models\ContratistaUnidadOrganizacional;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\CargoMandante;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DotacionAnteriorImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure, SkipsEmptyRows
{
    use Importable, RemembersRowNumber;

    // ─── Cache estático ────────────────────────────────────────────────────
    private static array $cache = [];

    // ─── Contadores ────────────────────────────────────────────────────────
    public int $activos      = 0;
    public int $nuevos       = 0;
    public int $finiquitados = 0;
    public int $movidos      = 0;
    public int $omitidos     = 0; // ya existían en la carpeta
    public array $failures   = [];

    public function __construct()
    {
        $this->initializeCache();
    }

    private function initializeCache(): void
    {
        if (empty(self::$cache)) {
            self::$cache['mandantes']   = Mandante::pluck('id', 'razon_social');
            self::$cache['contratistas'] = Contratista::pluck('id', 'rut');
        }
    }

    // ─── Lógica principal ─────────────────────────────────────────────────

    public function isEmptyWhen(array $row): bool
    {
        return empty(trim($row['id_registro'] ?? '')) && empty(trim($row['rut_trabajador'] ?? ''));
    }

    public function model(array $row)
    {
        // Ignorar filas vacías secundario (por si acaso)
        if ($this->isEmptyWhen($row)) {
            return null;
        }

        DB::beginTransaction();
        try {
            $idRegistro    = trim($row['id_registro']);
            $rutContratista = trim($row['rut_contratista']);
            $mandanteId    = self::$cache['mandantes'][trim($row['mandante'])] ?? null;
            $contratistaId = self::$cache['contratistas'][$rutContratista] ?? null;
            $rutTrabajador = trim($row['rut_trabajador']);
            $estado        = trim($row['estado']); // Activo | Nuevo | Finiquitado
            $cargoNombre   = trim($row['cargo'] ?? '');
            $lugar         = trim($row['lugar'] ?? '');
            $contrato      = trim($row['contrato'] ?? '');
            $periodo       = trim($row['periodo'] ?? '');

            // ── 1. Buscar el CUO (vinculación) por id_registro + mandante + lugar + contrato ──────
            $cuo = ContratistaUnidadOrganizacional::where('id_registro', $idRegistro)
                ->whereHas('unidadOrganizacionalMandante', fn($q) => $q->where('mandante_id', $mandanteId))
                ->where('contratista_id', $contratistaId)
                ->whereHas('dependencia', fn($q) => $q->where('nombre', $lugar))
                ->when($contrato, fn($q) => $q->where('numero_contrato', $contrato), fn($q) => $q->whereNull('numero_contrato'))
                ->first();

            if (!$cuo) {
                throw new \Exception("No se encontró la vinculación para ID_REGISTRO '$idRegistro' en '$lugar' con contrato '$contrato'.");
            }

            // ── 2. Extraer Año y Mes del PERIODO (Formato YYYY-MM) ─────────────
            $partes    = explode('-', $periodo);
            $anio      = (int) $partes[0];
            $mes       = (int) $partes[1];

            // ── 3. Buscar CarpetaVerificacion para ese Período ───────────
            $carpeta = CarpetaVerificacion::where('contratista_unidad_organizacional_id', $cuo->id)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->first();

            if (!$carpeta) {
                // El usuario asume que la dotación importada formará la base histórica validada
                $carpeta = CarpetaVerificacion::create([
                    'contratista_unidad_organizacional_id' => $cuo->id,
                    'anio'            => $anio,
                    'mes'             => $mes,
                    // Dejamos que tipo_envio tome su valor por defecto (NORMAL/MENSUAL) para evitar truncation.
                    'estado'          => 'ENVIADO',
                    'estado_revision' => 'AUDITADO',
                ]);
            }

            // ── 3. Buscar o crear el Trabajador ───────────────────────────────
            $esFiniquitado = strtolower($estado) === 'finiquitado';
            $esMovido      = strtolower($estado) === 'movido';
            $isActive = !$esFiniquitado && !$esMovido;

            $trabajador = Trabajador::where('rut', $rutTrabajador)
                ->where('contratista_id', $contratistaId)
                ->first();

            if (!$trabajador) {
                // Crear trabajador con datos mínimos del Excel
                $trabajador = Trabajador::create([
                    'contratista_id'   => $contratistaId,
                    'rut'              => $rutTrabajador,
                    'nombres'          => trim($row['nombres'] ?? 'SIN NOMBRE'),
                    'apellido_paterno' => trim($row['apellido_paterno'] ?? 'SIN APELLIDO'),
                    'apellido_materno' => trim($row['apellido_materno'] ?? '') ?: null,
                    'is_active'        => $isActive,
                ]);
            } else {
                // Si el trabajador existe y es finiquitado/movido, marcar como inactivo en contexto global 
                // (Aunque normalmente is_active en trabajador evalúa todo, lo dejamos false si el excel dice que se fue)
                if (($esFiniquitado || $esMovido) && $trabajador->is_active) {
                    $trabajador->update(['is_active' => false]);
                }
            }

            // ── 4. Buscar o crear TrabajadorVinculacion ───────────────────────
            $cargo = CargoMandante::where('mandante_id', $mandanteId)
                ->where('nombre_cargo', $cargoNombre)
                ->first();

            $vinculacion = TrabajadorVinculacion::where('trabajador_id', $trabajador->id)
                ->where('unidad_organizacional_mandante_id', $cuo->unidad_organizacional_mandante_id)
                ->where('dependencia_id', $cuo->dependencia_id)
                ->where('numero_contrato', $cuo->numero_contrato)
                ->first();

            if (!$vinculacion) {
                $fechaIngreso = null;
                if (!empty($row['fecha_ingreso'])) {
                    $fechaIngreso = is_numeric($row['fecha_ingreso'])
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_ingreso'])->format('Y-m-d')
                        : Carbon::createFromFormat('d-m-Y', $row['fecha_ingreso'])->format('Y-m-d');
                } else {
                    // Si viene vacío, usamos el primer día del PERIODO de la fila para coherencia histórica
                    $fechaIngreso = Carbon::create($anio, $mes, 1)->format('Y-m-d');
                }

                $fechaContrato = null;
                if (!empty($row['fecha_contrato'])) {
                    $fechaContrato = is_numeric($row['fecha_contrato'])
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_contrato'])->format('Y-m-d')
                        : Carbon::createFromFormat('d-m-Y', $row['fecha_contrato'])->format('Y-m-d');
                } else {
                    $fechaContrato = $fechaIngreso;
                }

                $fechaFin = null;
                if ($esFiniquitado && !empty($row['fecha_finiquito'])) {
                    $fechaFin = is_numeric($row['fecha_finiquito'])
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_finiquito'])->format('Y-m-d')
                        : Carbon::createFromFormat('d-m-Y', $row['fecha_finiquito'])->format('Y-m-d');
                }

                $motivoDesactivacion = null;
                if ($esFiniquitado) $motivoDesactivacion = 'FINIQUITO_DOTACION_ANTERIOR';
                if ($esMovido)      $motivoDesactivacion = 'MOVIDO_DOTACION_ANTERIOR';

                $vinculacion = TrabajadorVinculacion::create([
                    'trabajador_id'                      => $trabajador->id,
                    'unidad_organizacional_mandante_id'  => $cuo->unidad_organizacional_mandante_id,
                    'dependencia_id'                     => $cuo->dependencia_id,
                    'cargo_mandante_id'                  => $cargo?->id,
                    'numero_contrato'                    => $cuo->numero_contrato,
                    'fecha_ingreso_vinculacion'           => $fechaIngreso,
                    'fecha_contrato'                     => $fechaContrato,
                    'fecha_desactivacion'                => $fechaFin,
                    'motivo_desactivacion'               => $motivoDesactivacion,
                    'is_active'                          => $isActive,
                ]);
            }

            // ── 5. Verificar idempotencia en la Carpeta ───────────────────────
            $yaEnCarpeta = CarpetaVerificacionTrabajador::where('carpeta_verificacion_id', $carpeta->id)
                ->where('trabajador_vinculacion_id', $vinculacion->id)
                ->exists();

            if ($yaEnCarpeta) {
                DB::commit();
                $this->omitidos++;
                return null;
            }

            // ── 6. Determinar tipo_registro según estado ──────────────────────
            $tipoRegistro = match (strtolower($estado)) {
                'activo'      => 'DOTACION_ANTERIOR',
                'nuevo'       => 'VIGENTE',
                'finiquitado' => 'FINIQUITADO',
                'movido'      => 'MOVIDO',
                default       => 'DOTACION_ANTERIOR',
            };

            // ── 7. Crear registro en CarpetaVerificacionTrabajador ────────────
            CarpetaVerificacionTrabajador::create([
                'carpeta_verificacion_id'  => $carpeta->id,
                'trabajador_vinculacion_id' => $vinculacion->id,
                'tipo_registro'            => $tipoRegistro,
                'estado_revision'          => 'PENDIENTE',
                'observaciones'            => null,
            ]);

            DB::commit();

            // Incrementar contador correspondiente
            match (strtolower($estado)) {
                'activo'      => $this->activos++,
                'nuevo'       => $this->nuevos++,
                'finiquitado' => $this->finiquitados++,
                'movido'      => $this->movidos++,
                default       => $this->activos++,
            };

        } catch (\Exception $e) {
            DB::rollBack();
            $this->failures[] = [
                'row'       => $this->getRowNumber(),
                'attribute' => 'General',
                'errors'    => $e->getMessage(),
                'values'    => $row,
            ];
        }

        return null;
    }

    // ─── Validación de filas ───────────────────────────────────────────────

    public function rules(): array
    {
        return [
            'id_registro' => [
                'required', 'max:100',
                function ($attr, $value, $fail) {
                    $valStr = trim((string) $value);
                    $existe = DB::table('contratista_unidad_organizacional')
                        ->where('id_registro', $valStr)
                        ->exists();
                    if (!$existe) {
                        $fail("El ID_REGISTRO '$valStr' no existe en el sistema.");
                    }
                },
            ],
            'rut_contratista' => ['required', Rule::exists('contratistas', 'rut')],
            'mandante'        => [
                'required',
                function ($attr, $value, $fail) {
                    $valStr = trim((string) $value);
                    if (!isset(self::$cache['mandantes'][$valStr])) {
                        $fail("El Mandante '$valStr' no existe.");
                    }
                },
            ],
            'rut_trabajador'  => ['required', 'max:15'],
            'nombres'         => ['required', 'max:255'],
            'apellido_paterno' => ['required', 'max:255'],
            'apellido_materno' => ['nullable', 'max:255'],
            'cargo'           => ['required', 'max:255'],
            'lugar'           => ['required', 'max:255'],
            'contrato'        => ['nullable', 'max:100'],
            'estado'          => ['required', Rule::in(['Activo', 'Nuevo', 'Finiquitado', 'Movido'])],
            'periodo'         => ['required', 'regex:/^\d{4}-\d{1,2}$/'],
            'fecha_ingreso'   => ['nullable'],
            'fecha_contrato'  => ['nullable'],
            'fecha_finiquito' => [
                'nullable',
                function ($attr, $value, $fail, $validator) {
                    $estado = strtolower($validator->getData()['estado'] ?? '');
                    if ($estado === 'finiquitado' && empty($value)) {
                        $fail('La Fecha de Finiquito es obligatoria cuando el estado es Finiquitado.');
                    }
                },
            ],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'id_registro.required'     => 'El ID_REGISTRO es obligatorio.',
            'rut_contratista.required' => 'El RUT del Contratista es obligatorio.',
            'rut_contratista.exists'   => 'El RUT del Contratista no existe en el sistema.',
            'mandante.required'        => 'El Mandante es obligatorio.',
            'rut_trabajador.required'  => 'El RUT del Trabajador es obligatorio.',
            'nombres.required'         => 'Los Nombres son obligatorios.',
            'apellido_paterno.required' => 'El Apellido Paterno es obligatorio.',
            'cargo.required'           => 'El Cargo es obligatorio.',
            'lugar.required'           => 'El Lugar es obligatorio.',
            'contrato.required'        => 'El Contrato es obligatorio.',
            'estado.required'          => 'El Estado es obligatorio.',
            'estado.in'                => 'Estado inválido. Use: Activo, Nuevo, Finiquitado o Movido.',
            'periodo.required'         => 'El Período es obligatorio.',
            'periodo.regex'            => 'El formato del Período debe ser YYYY-MM (ejemplo: 2024-11).',
        ];
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row'       => $failure->row(),
                'attribute' => $this->translateAttribute($failure->attribute()),
                'errors'    => implode(', ', $failure->errors()),
                'values'    => $failure->values(),
            ];
        }
    }

    private function translateAttribute(string $attr): string
    {
        return [
            'id_registro'      => 'ID_REGISTRO',
            'rut_contratista'  => 'RUT Contratista',
            'mandante'         => 'Mandante',
            'rut_trabajador'   => 'RUT Trabajador',
            'nombres'          => 'Nombres',
            'apellido_paterno' => 'Apellido Paterno',
            'apellido_materno' => 'Apellido Materno',
            'cargo'            => 'Cargo',
            'lugar'            => 'Lugar',
            'contrato'         => 'Contrato',
            'estado'           => 'Estado',
            'fecha_ingreso'    => 'Fecha Ingreso',
            'fecha_finiquito'  => 'Fecha Finiquito',
            'periodo'          => 'Período',
        ][$attr] ?? str_replace('_', ' ', ucfirst($attr));
    }

    public function chunkSize(): int { return 100; }

    public static function clearCache(): void { self::$cache = []; }
}
