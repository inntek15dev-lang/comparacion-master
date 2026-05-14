<?php

namespace App\Imports;

use App\Models\VerificacionHistorica;
use App\Models\Mandante;
use App\Models\ContratistaUnidadOrganizacional;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VerificacionesHistoricasImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure
{
    use Importable, RemembersRowNumber;

    // ─── Cache estático para evitar N+1 ────────────────────────────────────
    private static array $cache = [];

    // ─── Contadores públicos ────────────────────────────────────────────────
    public int $successes = 0; // Registros nuevos creados
    public int $updated   = 0; // Registros existentes actualizados (idempotente)
    public array $failures = [];

    public function __construct()
    {
        $this->initializeCache();
    }

    private function initializeCache(): void
    {
        if (empty(self::$cache)) {
            // Mandantes: razón_social => id
            self::$cache['mandantes'] = Mandante::pluck('id', 'razon_social');

            // ID_REGISTROs válidos que existen en contratista_unidad_organizacional
            self::$cache['id_registros'] = DB::table('contratista_unidad_organizacional')
                ->whereNotNull('id_registro')
                ->pluck('id_registro')
                ->flip() // Para lookup O(1)
                ->all();
        }
    }

    // ─── Lógica de importación ──────────────────────────────────────────────

    public function model(array $row): ?VerificacionHistorica
    {
        // Saltar filas completamente vacías
        if (empty(trim($row['id_registro'] ?? '')) && empty(trim($row['mandante'] ?? ''))) {
            return null;
        }

        DB::beginTransaction();
        try {
            $idRegistro  = trim($row['id_registro']);
            $mandanteId  = self::$cache['mandantes'][trim($row['mandante'])] ?? null;
            $periodoAnio = (int) $row['periodo_anio'];
            $periodoMes  = (int) $row['periodo_mes'];
            $resultado   = trim($row['resultado']);
            $lugar       = trim($row['lugar']);
            $contrato    = trim($row['contrato']);
            $rutCont     = trim($row['rut_contratista'] ?? '');

            $montoRetenible    = isset($row['monto_retenible'])    && $row['monto_retenible']    !== ''
                                    ? (int) $row['monto_retenible']    : null;
            $montoNoRetenible  = isset($row['monto_no_retenible'])  && $row['monto_no_retenible']  !== ''
                                    ? (int) $row['monto_no_retenible']  : null;

            // Upsert idempotente: si ya existe, actualizar; si no, crear
            $existente = VerificacionHistorica::where('id_registro', $idRegistro)
                ->where('mandante_id', $mandanteId)
                ->where('periodo_anio', $periodoAnio)
                ->where('periodo_mes', $periodoMes)
                ->first();

            if ($existente) {
                $existente->update([
                    'lugar'              => $lugar,
                    'contrato'           => $contrato,
                    'resultado'          => $resultado,
                    'monto_retenible'    => $montoRetenible,
                    'monto_no_retenible' => $montoNoRetenible,
                    'importado_por'      => Auth::id(),
                ]);
                DB::commit();
                $this->updated++;
                return null; // ToModel sólo trabaja para creaciones; la actualización ya fue hecha
            }

            $nuevo = VerificacionHistorica::create([
                'id_registro'        => $idRegistro,
                'mandante_id'        => $mandanteId,
                'lugar'              => $lugar,
                'contrato'           => $contrato,
                'periodo_anio'       => $periodoAnio,
                'periodo_mes'        => $periodoMes,
                'resultado'          => $resultado,
                'monto_retenible'    => $montoRetenible,
                'monto_no_retenible' => $montoNoRetenible,
                'importado_por'      => Auth::id(),
            ]);

            DB::commit();
            $this->successes++;
            return null; // Ya guardamos manualmente; devolver null evita doble inserción

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

    // ─── Reglas de validación ───────────────────────────────────────────────

    public function rules(): array
    {
        return [
            // ID_REGISTRO: debe existir en contratista_unidad_organizacional
            'id_registro' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    if (!isset(self::$cache['id_registros'][trim($value)])) {
                        $fail("El ID_REGISTRO '$value' no existe en el sistema. Verifique que el contratista esté vinculado correctamente.");
                    }
                },
            ],

            // MANDANTE: debe existir por razón social
            'mandante' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!isset(self::$cache['mandantes'][trim($value)])) {
                        $fail("El Mandante '$value' no existe en el sistema.");
                    }
                },
            ],

            'lugar'    => ['required', 'string', 'max:255'],
            'contrato' => ['required', 'string', 'max:100'],

            // Período
            'periodo_anio' => ['required', 'numeric', 'min:2000', 'max:2099'],
            'periodo_mes'  => ['required', 'numeric', 'min:1',    'max:12'],

            // RESULTADO: obligatorio, sólo valores del enum
            'resultado' => [
                'required',
                Rule::in(['Limpio', 'Obs', 'Contingencia', 'Ambos']),
            ],

            // Montos: opcionales pero deben ser enteros no negativos si se informan
            'monto_retenible'   => ['nullable', 'numeric', 'min:0'],
            'monto_no_retenible' => ['nullable', 'numeric', 'min:0'],

            'rut_contratista' => ['nullable', 'string', 'max:15'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'id_registro.required'   => 'El ID_REGISTRO es obligatorio.',
            'mandante.required'      => 'El Mandante es obligatorio.',
            'lugar.required'         => 'El Lugar es obligatorio.',
            'contrato.required'      => 'El Contrato es obligatorio.',
            'periodo_anio.required'  => 'El Año del Período es obligatorio.',
            'periodo_anio.numeric'   => 'El Año debe ser un número (ej: 2024).',
            'periodo_mes.required'   => 'El Mes del Período es obligatorio.',
            'periodo_mes.min'        => 'El Mes debe estar entre 1 y 12.',
            'periodo_mes.max'        => 'El Mes debe estar entre 1 y 12.',
            'resultado.required'     => 'El Resultado es obligatorio.',
            'resultado.in'           => 'Resultado inválido. Use: Limpio, Obs, Contingencia o Ambos.',
            'monto_retenible.numeric'    => 'El Monto Retenible debe ser un número entero.',
            'monto_no_retenible.numeric' => 'El Monto No Retenible debe ser un número entero.',
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

    private function translateAttribute(string $attribute): string
    {
        return [
            'id_registro'        => 'ID_REGISTRO',
            'mandante'           => 'Mandante',
            'lugar'              => 'Lugar',
            'contrato'           => 'Contrato',
            'periodo_anio'       => 'Período Año',
            'periodo_mes'        => 'Período Mes',
            'resultado'          => 'Resultado',
            'monto_retenible'    => 'Monto Retenible',
            'monto_no_retenible' => 'Monto No Retenible',
            'rut_contratista'    => 'RUT Contratista',
        ][$attribute] ?? str_replace('_', ' ', ucfirst($attribute));
    }

    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Limpiar cache entre importaciones (si se reutiliza la instancia)
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
