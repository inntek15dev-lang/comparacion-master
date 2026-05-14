<?php

namespace App\Imports;

use App\Models\Vehiculo;
use App\Models\VehiculoAsignacion;
use App\Models\Contratista;
use App\Models\MarcaVehiculo;
use App\Models\TipoVehiculo;
use App\Models\ColorVehiculo;
use App\Models\TenenciaVehiculo;
use App\Models\SubTipoVehiculoMandante;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehiculosImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure
{
    use Importable, RemembersRowNumber;

    private static $cache = [];
    public $successes = 0;
    public $failures = [];

    public function __construct()
    {
        $this->initializeCache();
    }

    private function initializeCache()
    {
        if (empty(self::$cache)) {
            self::$cache['contratistas'] = Contratista::with([
                'unidadesOrganizacionalesMandante' => fn ($q) => $q->with('parent'),
                'dependencias' => fn ($q) => $q->with('parent')
            ])->get()->keyBy('rut');

            self::$cache['marcas'] = MarcaVehiculo::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['tipos'] = TipoVehiculo::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['colores'] = ColorVehiculo::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['tenencias'] = TenenciaVehiculo::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['mandantes'] = \App\Models\Mandante::pluck('id', 'razon_social');

            // SubTipos indexados como 'mandanteId|nombre' para búsqueda eficiente
            self::$cache['subtipos'] = SubTipoVehiculoMandante::where('is_active', true)
                ->get()
                ->mapWithKeys(function ($st) {
                    return [($st->mandante_id . '|' . strtoupper(trim($st->nombre))) => $st->id];
                });
        }
    }

    private function splitPatente(string $patenteCompleta): array
    {
        $patenteLimpia = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $patenteCompleta));
        preg_match('/^([A-Z]{2,4})([0-9]{2,4})$/', $patenteLimpia, $matches);

        if (count($matches) === 3) {
            return ['letras' => $matches[1], 'numeros' => $matches[2]];
        }
        
        $letras = preg_replace('/[0-9]/', '', $patenteLimpia);
        $numeros = preg_replace('/[A-Z]/i', '', $patenteLimpia);

        return ['letras' => $letras, 'numeros' => $numeros];
    }

    // ================== INICIO DE LA MODIFICACIÓN (FUNCIÓN DE NORMALIZACIÓN DEFINITIVA) ==================
    /**
     * Normaliza un string para comparación, eliminando todo tipo de espacios en blanco (incluyendo no-ruptura).
     */
    private function normalizeStringForComparison(string $str): string
    {
        // 1. Reemplaza CUALQUIER tipo de caracter de espacio en blanco (incluyendo &nbsp;) con un espacio normal.
        // La bandera 'u' (Unicode) es la clave para que \s reconozca espacios de no-ruptura.
        $str = preg_replace('/\s+/u', ' ', $str);

        // 2. Elimina espacios al principio y al final.
        $str = trim($str);

        // 3. Normaliza el separador para que no tenga espacios alrededor.
        $str = preg_replace('/\s*<\s*/u', '<', $str);
        
        $str = preg_replace('/\s*<\s*/u', '<', $str);
        
        return $str;
    }

    /**
     * Extrae el nombre real del recurso cuando viene en formato compuesto: "MANDANTE — RECURSO"
     */
    private static function cleanCompositeName(?string $value): string
    {
        if (!$value) return '';
        $parts = explode(' — ', trim($value));
        return trim(end($parts));
    }

    /**
     * Extrae el nombre del mandante cuando viene en formato compuesto: "MANDANTE — RECURSO"
     */
    private static function extractMandanteName(?string $value): string
    {
        if (!$value) return '';
        $parts = explode(' — ', trim($value));
        return count($parts) > 1 ? trim($parts[0]) : '';
    }

    /**
     * Resuelve el sub_tipo_vehiculo_mandante_id a partir del row y la UO ya resuelta.
     * La columna 'sub_tipo_vehiculo' viene en formato "MANDANTE — SUBTIPO" o solo "SUBTIPO".
     * El mandante se obtiene de la UO que ya fue resuelta.
     */
    private function resolverSubtipo(array $row, $uo, $mandanteIdFallback = null): ?int
    {
        $rawSubtipo = trim($row['sub_tipo_vehiculo_opcional'] ?? $row['sub_tipo_vehiculo'] ?? '');
        if (empty($rawSubtipo)) return null;

        $nombreSubtipo = strtoupper(self::cleanCompositeName($rawSubtipo));
        if (empty($nombreSubtipo)) return null;

        $mandanteId = $uo ? $uo->mandante_id : $mandanteIdFallback;
        if (!$mandanteId) return null;

        $key = $mandanteId . '|' . $nombreSubtipo;

        return self::$cache['subtipos'][$key] ?? null;
    }

    public function model(array $row)
    {
        if (empty(trim($row['rut_contratista'] ?? '')) || empty(trim($row['patente'] ?? ''))) {
            return null;
        }

        DB::beginTransaction();
        try {
            $contratista = self::$cache['contratistas'][trim($row['rut_contratista'])] ?? null;
            if (!$contratista) {
                throw new \Exception("Contratista no encontrado. La validación debería haber prevenido esto.");
            }

            $uoNombre = self::cleanCompositeName($row['nombre_uo_inicial'] ?? '');
            $depNombre = self::cleanCompositeName($row['nombre_lugar_de_trabajo_inicial'] ?? '');

            $uo = null;
            $dependencia = null;
            $mandanteIdParaReserva = null;

            if (!empty($uoNombre) && !empty($depNombre)) {
                $uoNormalizado = $this->normalizeStringForComparison($uoNombre);
                $dependenciaNormalizado = $this->normalizeStringForComparison($depNombre);

                $uo = $contratista->unidadesOrganizacionalesMandante->first(fn ($item) => $this->normalizeStringForComparison($item->nombre_jerarquico) === $uoNormalizado);
                $dependencia = $contratista->dependencias->first(fn ($item) => $this->normalizeStringForComparison($item->nombre_jerarquico) === $dependenciaNormalizado);

                if (!$uo) throw new \Exception("La U.O. '{$row['nombre_uo_inicial']}' no se encontró para el contratista.");
                if (!$dependencia) throw new \Exception("El Lugar de Trabajo '{$row['nombre_lugar_de_trabajo_inicial']}' no se encontró para el contratista.");
            } else {
                // Si faltan UO/Lugar, intentamos obtener el mandante del Sub-Tipo
                $rawSubtipo = trim($row['sub_tipo_vehiculo_opcional'] ?? $row['sub_tipo_vehiculo'] ?? '');
                $nombreMandante = self::extractMandanteName($rawSubtipo);
                $mandanteIdParaReserva = self::$cache['mandantes'][$nombreMandante] ?? null;

                if (!$mandanteIdParaReserva) {
                    throw new \Exception("No se proporcionó UO/Lugar inicial. Para cargar en RESERVA, debe indicar un Sub-Tipo de vehículo que identifique al Principal.");
                }
            }

            $patenteData = $this->splitPatente($row['patente']);

            $vehiculo = Vehiculo::create([
                'contratista_id' => $contratista->id,
                'patente_letras' => $patenteData['letras'],
                'patente_numeros' => $patenteData['numeros'],
                'ano_fabricacion' => $row['ano_fabricacion'],
                'marca_vehiculo_id' => self::$cache['marcas'][trim($row['marca'])] ?? null,
                'tipo_vehiculo_id' => self::$cache['tipos'][trim($row['tipo'])] ?? null,
                'color_vehiculo_id' => self::$cache['colores'][trim($row['color'])] ?? null,
                'tenencia_vehiculo_id' => isset($row['tenencia']) ? (self::$cache['tenencias'][trim($row['tenencia'])] ?? null) : null,
                'is_active' => true,
            ]);

            VehiculoAsignacion::create([
                'vehiculo_id'                         => $vehiculo->id,
                'unidad_organizacional_mandante_id'   => $uo ? $uo->id : null,
                'dependencia_id'                      => $dependencia ? $dependencia->id : null,
                'sub_tipo_vehiculo_mandante_id'        => $this->resolverSubtipo($row, $uo, $mandanteIdParaReserva),
                'fecha_asignacion'                    => now(),
                'is_active'                           => true,
            ]);

            DB::commit();
            $this->successes++;
            return $vehiculo;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en importación de vehículos (Fila {$this->getRowNumber()}): " . $e->getMessage());
            $this->failures[] = [
                'row' => $this->getRowNumber(),
                'attribute' => 'Procesamiento General',
                'errors' => 'Error: ' . $e->getMessage(),
                'values' => $row,
            ];
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'rut_contratista' => ['required', Rule::exists('contratistas', 'rut')],
            'patente' => ['required', 'string', 'min:4', 'max:10', function ($attribute, $value, $fail, $validator) {
                $rutContratista = $validator->getData()['rut_contratista'] ?? null;
                if (!$rutContratista) return;

                $contratista = self::$cache['contratistas'][trim($rutContratista)] ?? null;
                if (!$contratista) return;

                $patenteData = $this->splitPatente($value);
                if (empty($patenteData['letras']) || empty($patenteData['numeros'])) {
                    $fail('El formato de la patente no es válido. Debe contener letras y números (ej: ABCD1234).');
                    return;
                }

                $exists = Vehiculo::where('contratista_id', $contratista->id)
                    ->where('patente_letras', $patenteData['letras'])
                    ->where('patente_numeros', $patenteData['numeros'])
                    ->exists();

                if ($exists) {
                    $fail("La patente '$value' ya existe para el contratista con RUT $rutContratista.");
                }
            }],
            'ano_fabricacion' => ['required', 'integer', 'digits:4', 'min:1950', 'max:' . (date('Y') + 1)],
            'marca' => ['required', Rule::exists('marcas_vehiculo', 'nombre')],
            'tipo' => ['required', Rule::exists('tipos_vehiculo', 'nombre')],
            'color' => ['required', Rule::exists('colores_vehiculo', 'nombre')],
            'tenencia' => ['nullable', Rule::exists('tenencias_vehiculo', 'nombre')],
            
            'nombre_uo_inicial' => ['nullable', 'string', function ($attribute, $value, $fail, $validator) {
                if (empty($value)) return;

                $rutContratista = $validator->getData()['rut_contratista'] ?? null;
                if (!$rutContratista) return;
                
                $contratista = self::$cache['contratistas'][trim($rutContratista)] ?? null;
                if (!$contratista) return;

                $valorLimpio = self::cleanCompositeName($value);
                $valorNormalizado = $this->normalizeStringForComparison($valorLimpio);
                $uo = $contratista->unidadesOrganizacionalesMandante->first(fn ($item) => $this->normalizeStringForComparison($item->nombre_jerarquico) === $valorNormalizado);

                if (!$uo) {
                    $fail("La U.O. '$value' no existe o no está asignada al contratista con RUT $rutContratista.");
                }
            }],
            'nombre_lugar_de_trabajo_inicial' => ['nullable', 'string', function ($attribute, $value, $fail, $validator) {
                $data = $validator->getData();
                $uoNombre = trim($data['nombre_uo_inicial'] ?? '');
                
                // Si hay UO, el Lugar es obligatorio. Si no hay UO, puede ser null (Reserva)
                if (!empty($uoNombre) && empty($value)) {
                    $fail("Si se indica una U.O. Inicial, el Lugar de Trabajo es obligatorio.");
                    return;
                }

                if (empty($value)) {
                    // Si ambos son null, verificar que haya Sub-Tipo para el mandante
                    $subtipo = trim($data['sub_tipo_vehiculo_opcional'] ?? $data['sub_tipo_vehiculo'] ?? '');
                    if (empty($subtipo)) {
                        $fail("Debe indicar U.O. e Inicial, o al menos un Sub-Tipo para cargar en RESERVA.");
                    } else {
                        $nombreMandante = self::extractMandanteName($subtipo);
                        if (!isset(self::$cache['mandantes'][$nombreMandante])) {
                            $fail("No se pudo identificar al Principal desde el Sub-Tipo '$subtipo'. Verifique el formato 'MANDANTE — SUBTIPO'.");
                        }
                    }
                    return;
                }

                $rutContratista = $data['rut_contratista'] ?? null;
                if (!$rutContratista) return;

                $contratista = self::$cache['contratistas'][trim($rutContratista)] ?? null;
                if (!$contratista) return;

                $valorLimpio = self::cleanCompositeName($value);
                $valorNormalizado = $this->normalizeStringForComparison($valorLimpio);
                $dependencia = $contratista->dependencias->first(fn ($item) => $this->normalizeStringForComparison($item->nombre_jerarquico) === $valorNormalizado);

                if (!$dependencia) {
                    $fail("El Lugar de Trabajo '$value' no existe o no está asignada al contratista con RUT $rutContratista.");
                }
            }],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'rut_contratista.exists' => 'El RUT del Contratista no fue encontrado en el sistema.',
            '*.required' => 'El campo :attribute es obligatorio.',
            '*.exists' => 'El valor para :attribute no es válido o no existe en el sistema.',
            'ano_fabricacion.max' => 'El año de fabricación no puede ser futuro.',
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
            'ano_fabricacion' => 'Año Fabricación',
            'nombre_uo_inicial' => 'Nombre U.O. Inicial',
            'nombre_lugar_de_trabajo_inicial' => 'Nombre Lugar de Trabajo Inicial',
        ][$attribute] ?? str_replace('_', ' ', ucfirst($attribute));
    }

    public function chunkSize(): int
    {
        return 50;
    }
}