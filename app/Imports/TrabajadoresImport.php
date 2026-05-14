<?php

namespace App\Imports;

use App\Models\Trabajador;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\Nacionalidad;
use App\Models\Sexo;
use App\Models\EstadoCivil;
use App\Models\NivelEducacional;
use App\Models\Etnia;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\Dependencia;
use App\Models\CargoMandante;
use App\Models\TrabajadorVinculacion;
use App\Models\SolicitudVinculacion;
use App\Models\TipoPermanencia;
use App\Rules\ValidarRutRule;
use App\Rules\ExcelDateFormatRule;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TrabajadoresImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsEmptyRows, SkipsOnFailure
{
    use Importable, RemembersRowNumber;

    private static $cache = [];
    public $successes = 0;
    public $updates   = 0;
    public $warnings  = [];  // Trabajadores creados en Reserva por cuota excedida
    public $failures  = [];

    public function __construct()
    {
        $this->initializeCache();
    }

    private function initializeCache()
    {
        if (empty(self::$cache)) {
            // Solo catálogos livianos. Contratistas se cargan lazily por RUT.
            self::$cache['mandantes']             = Mandante::pluck('id', 'razon_social');
            self::$cache['nacionalidades']        = Nacionalidad::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['tipos_permanencia']     = TipoPermanencia::pluck('id', 'nombre');
            self::$cache['sexos']                 = Sexo::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['estados_civiles']       = EstadoCivil::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['etnias']                = Etnia::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['niveles_educacionales'] = NivelEducacional::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['contratistas']          = []; // se llena lazily
        }
    }

    /**
     * Devuelve el contratista desde cache. Si no está, lo consulta y lo guarda.
     * Evita cargar TODOS los contratistas en memoria al inicio.
     */
    private function getContratista(string $rut): ?Contratista
    {
        $rut = trim($rut);
        if (!array_key_exists($rut, self::$cache['contratistas'])) {
            self::$cache['contratistas'][$rut] = Contratista::with([
                'unidadesOrganizacionalesMandante' => fn($q) => $q->with('parent'),
                'dependencias'                     => fn($q) => $q->with('parent'),
                'mandantesAprobados',
            ])->where('rut', $rut)->first();
        }
        return self::$cache['contratistas'][$rut];
    }

    /**
     * Extrae el nombre real del recurso cuando viene en formato compuesto: "MANDANTE — RECURSO"
     */
    private static function cleanCompositeName(?string $value): string
    {
        if (!$value)
            return '';
        $parts = explode(' — ', trim($value));
        return trim(end($parts));
    }

    /**
     * Define cuándo una fila se considera vacía para omitirla ANTES de validar.
     * Esto soluciona el problema de Excel que envía celdas con espacios o formato como si tuvieran datos.
     */
    public function isEmptyWhen(array $row): bool
    {
        return empty(trim($row['rut_contratista'] ?? '')) && empty(trim($row['rut_trabajador'] ?? ''));
    }

    public function model(array $row)
    {
        if (empty(trim($row['rut_contratista'] ?? '')) || empty(trim($row['rut_trabajador'] ?? ''))) {
            return null;
        }

        DB::beginTransaction();
        try {
            // 1. Parsear fecha
            $fechaValue = $row['fecha_de_nacimiento'];
            if (is_numeric($fechaValue)) {
                $fechaNacimiento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fechaValue)->format('Y-m-d');
            } else {
                $parsed = null;
                foreach (['d-m-Y', 'd/m/Y', 'd/m/y', 'd-m-y'] as $fmt) {
                    try { $parsed = Carbon::createFromFormat($fmt, $fechaValue); break; } catch (\Exception $e) {}
                }
                if (!$parsed) throw new \Exception("Formato de fecha inválido: '{$fechaValue}'. Use dd-mm-aaaa.");
                $fechaNacimiento = $parsed->format('Y-m-d');
            }

            // 2. Resolver Contratista y Mandante
            $contratista = $this->getContratista($row['rut_contratista'] ?? '');
            $mandanteId  = self::$cache['mandantes'][trim($row['razon_social_principal'])] ?? null;
            if (!$contratista || !$mandanteId) {
                throw new \Exception("Contratista o Principal no encontrado.");
            }

            // 3. Resolver UO, Lugar, Cargo
            $uoName   = self::cleanCompositeName($row['nombre_uo_inicial'] ?? '');
            $uo       = $contratista->unidadesOrganizacionalesMandante->first(
                fn($u) => $u->mandante_id == $mandanteId && $u->nombre_jerarquico === $uoName
            );
            $depName     = self::cleanCompositeName($row['nombre_lugar_de_trabajo_inicial'] ?? '');
            $dependencia = $contratista->dependencias->first(
                fn($d) => $d->mandante_id == $mandanteId && $d->nombre_jerarquico === $depName
            );
            $cargoName = self::cleanCompositeName($row['nombre_cargo_inicial'] ?? '');
            $cargo     = CargoMandante::where('mandante_id', $mandanteId)->where('nombre_cargo', $cargoName)->first();

            if (!$uo || !$dependencia || !$cargo) {
                $missing = array_filter(['U.O.' => !$uo, 'Lugar de Trabajo' => !$dependencia, 'Cargo' => !$cargo]);
                throw new \Exception("No encontrado: " . implode(', ', array_keys($missing)));
            }

            $numeroContrato = !empty(trim($row['numero_contrato'] ?? '')) ? trim($row['numero_contrato']) : null;

            // 4. Datos del trabajador (reutilizados en create y update)
            $trabajadorData = [
                'contratista_id'                        => $contratista->id,
                'rut'                                   => trim($row['rut_trabajador']),
                'nombres'                               => $row['nombres'],
                'apellido_paterno'                      => $row['apellido_paterno'],
                'apellido_materno'                      => $row['apellido_materno'] ?? null,
                'fecha_nacimiento'                      => $fechaNacimiento,
                'email'                                 => $row['email'] ?? null,
                'celular'                               => $row['telefono'] ?? null,
                'direccion_calle'                       => $row['direccion_calle_y_numero'] ?? null,
                'nacionalidad_id'                       => self::$cache['nacionalidades'][trim($row['nacionalidad'])]          ?? null,
                'tipo_permanencia_id'                   => self::$cache['tipos_permanencia'][trim($row['tipo_permanencia'])]   ?? null,
                'sexo_id'                               => isset($row['sexo'])              ? (self::$cache['sexos'][trim($row['sexo'])]                                       ?? null) : null,
                'estado_civil_id'                       => isset($row['estado_civil'])      ? (self::$cache['estados_civiles'][trim($row['estado_civil'])]                   ?? null) : null,
                'etnia_id'                              => isset($row['etnia'])              ? (self::$cache['etnias'][trim($row['etnia'])]                                     ?? null) : null,
                'nivel_educacional_id'                  => isset($row['nivel_educacional']) ? (self::$cache['niveles_educacionales'][trim($row['nivel_educacional'])]       ?? null) : null,
                'is_active'                             => true,
            ];

            // ================================================================
            // 5. UPSERT: actualizar si existe, crear si no
            // ================================================================
            $trabajadorExistente = Trabajador::where('rut', trim($row['rut_trabajador']))
                ->where('contratista_id', $contratista->id)
                ->first();

            if ($trabajadorExistente) {
                // ── MODO UPDATE ──────────────────────────────────────────────
                $trabajadorExistente->update($trabajadorData);

                // Actualizar la vinculación específica identificada por numero_contrato.
                // Si el trabajador tiene 2 contratos (2 filas en Excel), cada fila actualiza la suya.
                $vinculacion = TrabajadorVinculacion::where('trabajador_id', $trabajadorExistente->id)
                    ->where('is_active', true)
                    ->where(function ($q) use ($numeroContrato) {
                        if (is_null($numeroContrato)) {
                            $q->whereNull('numero_contrato');
                        } else {
                            $q->where('numero_contrato', $numeroContrato);
                        }
                    })
                    ->first();

                if ($vinculacion) {
                    $vinculacion->update([
                        'unidad_organizacional_mandante_id' => $uo->id,
                        'dependencia_id'                    => $dependencia->id,
                        'cargo_mandante_id'                 => $cargo->id,
                        'numero_contrato'                   => $numeroContrato,
                    ]);
                } else {
                    // No existe esa vinculación → crear una nueva para este contrato
                    TrabajadorVinculacion::create([
                        'trabajador_id'                     => $trabajadorExistente->id,
                        'unidad_organizacional_mandante_id' => $uo->id,
                        'dependencia_id'                    => $dependencia->id,
                        'cargo_mandante_id'                 => $cargo->id,
                        'numero_contrato'                   => $numeroContrato,
                        'fecha_ingreso_vinculacion'         => now(),
                        'is_active'                         => true,
                    ]);
                }

                DB::commit();
                $this->updates++;
                return null; // ToModel espera null en updates (no hace re-insert)
            }

            // ── MODO CREATE ──────────────────────────────────────────────────
            // Validación de cuota (solo para trabajadores nuevos)
            $enReservaPorCuota = false;
            $cuo = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $contratista->id)
                ->where('unidad_organizacional_mandante_id', $uo->id)
                ->where('dependencia_id', $dependencia->id)
                ->whereNull('numero_contrato')
                ->first();

            if ($cuo && !is_null($cuo->trabajadores_cuota)) {
                $getFamilyIds = function ($cId, $mId) {
                    $currentId = $cId;
                    while (true) {
                        $sol = \App\Models\SolicitudVinculacion::where('contratista_id', $currentId)->where('mandante_id', $mId)->where('estado', 'APROBADA')->first();
                        if (!$sol || !$sol->contratista_padre_id) break;
                        $currentId = $sol->contratista_padre_id;
                    }
                    $rootId    = $currentId;
                    $familyIds = [$rootId];
                    $lvl1      = \App\Models\SolicitudVinculacion::where('contratista_padre_id', $rootId)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                    $familyIds = array_merge($familyIds, $lvl1);
                    if (!empty($lvl1)) {
                        $lvl2 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl1)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                        $familyIds = array_merge($familyIds, $lvl2);
                        if (!empty($lvl2)) {
                            $lvl3 = \App\Models\SolicitudVinculacion::whereIn('contratista_padre_id', $lvl2)->where('mandante_id', $mId)->where('estado', 'APROBADA')->pluck('contratista_id')->toArray();
                            $familyIds = array_merge($familyIds, $lvl3);
                        }
                    }
                    return array_unique($familyIds);
                };
                $familyIds   = $getFamilyIds($contratista->id, $mandanteId);
                $activeCount = TrabajadorVinculacion::whereHas('trabajador', fn($q) => $q->whereIn('contratista_id', $familyIds))
                    ->where('unidad_organizacional_mandante_id', $uo->id)
                    ->where('dependencia_id', $dependencia->id)
                    ->where('is_active', true)
                    ->count();
                if ($activeCount >= $cuo->trabajadores_cuota) {
                    $enReservaPorCuota = true;
                }
            }

            $trabajador = Trabajador::create($trabajadorData);

            if ($enReservaPorCuota) {
                TrabajadorVinculacion::create([
                    'trabajador_id'                    => $trabajador->id,
                    'unidad_organizacional_mandante_id' => null,
                    'dependencia_id'                   => null,
                    'cargo_mandante_id'                => null,
                    'numero_contrato'                  => null,
                    'fecha_ingreso_vinculacion'        => now(),
                    'is_active'                        => true,
                ]);
                DB::commit();
                $this->successes++;
                $this->warnings[] = [
                    'row'        => $this->getRowNumber(),
                    'trabajador' => $row['nombres'] . ' ' . $row['apellido_paterno'] . ' (' . $row['rut_trabajador'] . ')',
                    'motivo'     => "Cuota de {$cuo->trabajadores_cuota} trabajadores alcanzada en '{$depName} / {$uoName}'. Creado en RESERVA.",
                ];
                return $trabajador;
            }

            TrabajadorVinculacion::create([
                'trabajador_id'                    => $trabajador->id,
                'unidad_organizacional_mandante_id' => $uo->id,
                'dependencia_id'                   => $dependencia->id,
                'cargo_mandante_id'                => $cargo->id,
                'numero_contrato'                  => $numeroContrato,
                'fecha_ingreso_vinculacion'        => now(),
                'is_active'                        => true,
            ]);

            DB::commit();
            $this->successes++;
            return $trabajador;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->failures[] = [
                'row'       => $this->getRowNumber(),
                'attribute' => 'General',
                'errors'    => 'Error al procesar: ' . $e->getMessage(),
                'values'    => $row,
            ];
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'rut_contratista' => ['required', Rule::exists('contratistas', 'rut')],
            'razon_social_principal' => [
                'required',
                'string',
                function ($attribute, $value, $fail, $validator) {
                    $rutContratista = $validator->getData()['rut_contratista'] ?? null;
                    if (!$rutContratista)
                        return;

                    $contratista = self::$cache['contratistas'][trim($rutContratista)] ?? null;
                    if (!$contratista)
                        return;

                    $mandante = $contratista->mandantesAprobados->firstWhere('razon_social', trim($value));
                    if (!$mandante) {
                        $fail("El Principal '$value' no existe o el Contratista no tiene una vinculación aprobada con él.");
                    }
                }
            ],
            // RUT Trabajador: solo validar formato. Si ya existe → se actualizará (upsert).
            'rut_trabajador' => [
                'required',
                new ValidarRutRule(),
            ],
            'nombres' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'fecha_de_nacimiento' => ['required', new ExcelDateFormatRule('d-m-Y')],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'direccion_calle_y_numero' => ['nullable', 'string', 'max:255'],
            'nacionalidad' => ['required', Rule::exists('nacionalidades', 'nombre')],
            'tipo_permanencia' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!isset(self::$cache['tipos_permanencia'][trim($value)])) {
                        $fail("El Tipo de Permanencia '{$value}' no existe en el sistema.");
                    }
                }
            ],
            'sexo' => ['nullable', Rule::exists('sexos', 'nombre')],
            'estado_civil' => ['nullable', Rule::exists('estados_civiles', 'nombre')],
            'etnia' => ['nullable', Rule::exists('etnias', 'nombre')],
            'nivel_educacional' => ['nullable', Rule::exists('niveles_educacionales', 'nombre')],

            'nombre_uo_inicial' => [
                'required',
                'string',
                function ($attribute, $value, $fail, $validator) {
                    $data = $validator->getData();
                    $rutContratista = $data['rut_contratista'] ?? null;
                    $razonSocialPrincipal = $data['razon_social_principal'] ?? null;
                    if (!$rutContratista || !$razonSocialPrincipal)
                        return;

                    $contratista = $this->getContratista($rutContratista);
                    $mandanteId = self::$cache['mandantes'][trim($razonSocialPrincipal)] ?? null;
                    if (!$contratista || !$mandanteId)
                        return;

                    $uoName = self::cleanCompositeName($value);
                    $uo = $contratista->unidadesOrganizacionalesMandante->first(function ($uo) use ($uoName, $mandanteId) {
                        return $uo->mandante_id == $mandanteId && $uo->nombre_jerarquico === $uoName;
                    });

                    if (!$uo) {
                        $fail("La U.O. '$value' no existe para el Principal '$razonSocialPrincipal' o no está asignada al Contratista.");
                    }
                }
            ],
            'nombre_lugar_de_trabajo_inicial' => [
                'required',
                'string',
                function ($attribute, $value, $fail, $validator) {
                    $data = $validator->getData();
                    $rutContratista = $data['rut_contratista'] ?? null;
                    $razonSocialPrincipal = $data['razon_social_principal'] ?? null;
                    if (!$rutContratista || !$razonSocialPrincipal)
                        return;

                    $contratista = $this->getContratista($rutContratista);
                    $mandanteId = self::$cache['mandantes'][trim($razonSocialPrincipal)] ?? null;
                    if (!$contratista || !$mandanteId)
                        return;

                    $depName = self::cleanCompositeName($value);
                    $dependencia = $contratista->dependencias->first(function ($dep) use ($depName, $mandanteId) {
                        return $dep->mandante_id == $mandanteId && $dep->nombre_jerarquico === $depName;
                    });

                    if (!$dependencia) {
                        $fail("El Lugar de Trabajo '$value' no existe para el Principal '$razonSocialPrincipal' o no está asignado al Contratista.");
                    }
                }
            ],
            'nombre_cargo_inicial' => [
                'required',
                'string',
                function ($attribute, $value, $fail, $validator) {
                    $razonSocialPrincipal = $validator->getData()['razon_social_principal'] ?? null;
                    if (!$razonSocialPrincipal)
                        return;

                    $mandanteId = self::$cache['mandantes'][trim($razonSocialPrincipal)] ?? null;
                    if (!$mandanteId)
                        return;

                    $cargoName = self::cleanCompositeName($value);
                    $cargo = CargoMandante::where('mandante_id', $mandanteId)->where('nombre_cargo', $cargoName)->exists();
                    if (!$cargo) {
                        $fail("El Cargo '$value' no existe para el Principal '$razonSocialPrincipal'.");
                    }
                }
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'rut_contratista.exists' => 'El RUT del Contratista no fue encontrado en el sistema.',
            'rut_trabajador.unique' => 'El RUT del Trabajador ya existe en el sistema.',
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor para :attribute no es válido.',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $this->translateAttribute($failure->attribute()),
                'errors' => implode(', ', $failure->errors()),
                'values' => $failure->values(),
            ];
        }
    }

    private function translateAttribute(string $attribute): string
    {
        return [
            'rut_contratista' => 'RUT Contratista',
            'razon_social_principal' => 'Razón Social Principal',
            'rut_trabajador' => 'RUT Trabajador',
            'fecha_de_nacimiento' => 'Fecha de Nacimiento',
            'nombre_uo_inicial' => 'Nombre U.O. Inicial',
            'nombre_lugar_de_trabajo_inicial' => 'Nombre Lugar de Trabajo Inicial',
            'nombre_cargo_inicial' => 'Nombre Cargo Inicial',
        ][$attribute] ?? str_replace('_', ' ', ucfirst($attribute));
    }

    public function chunkSize(): int
    {
        return 50;
    }
}