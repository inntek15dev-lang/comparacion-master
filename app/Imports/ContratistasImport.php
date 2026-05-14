<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Mandante;
use App\Models\Comuna;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\SolicitudVinculacion;
use App\Rules\ValidarRutRule;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use App\Models\Contratista as ContratistaModel;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;

class ContratistasImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, SkipsOnFailure
{
    use Importable, RemembersRowNumber;

    private static $cache = [];
    private $adminUserId;

    public $successes = 0;
    public $failures = [];
    public $passwords = [];

    public function __construct()
    {
        $this->adminUserId = auth()->id();
        $this->initializeCache();
    }

    private function initializeCache()
    {
        if (empty(self::$cache)) {
            self::$cache['mandantes'] = Mandante::where('is_active', true)->pluck('id', 'razon_social');
            
            $comunasCollection = Comuna::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['comunas'] = $comunasCollection->mapWithKeys(function ($id, $nombre) {
                return [strtolower(trim($nombre)) => $id];
            });

            self::$cache['tipos_empresa'] = TipoEmpresaLegal::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['rubros'] = Rubro::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['rangos'] = RangoCantidadTrabajadores::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['mutualidades'] = Mutualidad::where('is_active', true)->pluck('id', 'nombre');
            self::$cache['contratista_admin_role'] = Role::where('name', 'Contratista_Admin')->firstOrFail();
        }
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

    public function model(array $row)
    {
        if (empty(trim($row['razon_social'] ?? ''))) {
            return null;
        }

        $contratistaId = null;
        DB::beginTransaction();
        try {
            $rutEmpresa = trim($row['rut_empresa']);
            $contratista = ContratistaModel::where('rut', $rutEmpresa)->first();
            $nivelJerarquia = (int) ($row['nivel_jerarquia'] ?? 0);
            $rutPadre = trim($row['rut_contratista_padre'] ?? '');

            if ($contratista) {
                $contratistaId = $contratista->id;
                // Upsert User
                $user = User::where('id', $contratista->admin_user_id)->orWhere('email', trim($row['admin_email']))->first();
                if ($user) {
                    $userData = [];
                    if (!empty($row['admin_nombre_completo'])) $userData['name'] = $row['admin_nombre_completo'];
                    if (!empty($row['admin_rut'])) $userData['rut'] = $row['admin_rut'];
                    if (!empty($row['admin_email'])) $userData['email'] = $row['admin_email'];
                    
                    if (!empty($userData)) {
                        $user->update($userData);
                    }
                } else {
                    // Solo creamos si tenemos el email al menos
                    if (!empty($row['admin_email'])) {
                        $generatedPassword = Str::random(12);
                        $user = User::create([
                            'name' => $row['admin_nombre_completo'] ?? 'S/D Admin',
                            'rut' => $row['admin_rut'] ?? null,
                            'email' => $row['admin_email'],
                            'password' => Hash::make($generatedPassword),
                            'is_active' => true,
                            'user_type' => 'Contratista',
                            'contratista_id' => $contratistaId,
                        ]);
                        $user->roles()->attach(self::$cache['contratista_admin_role']);
                        $this->passwords[] = ['email' => $user->email, 'password' => $generatedPassword];
                        
                        $contratista->update(['admin_user_id' => $user->id]);
                    }
                }
            } else {
                // Verificar si el email ya existe para otro usuario distinto
                if (User::where('email', trim($row['admin_email']))->exists()) {
                    throw new \Exception("El email del administrador ya está en uso por otro usuario.");
                }

                $generatedPassword = Str::random(12);
                $user = User::create([
                    'name' => $row['admin_nombre_completo'] ?? 'S/D Admin',
                    'rut' => $row['admin_rut'] ?? null,
                    'email' => $row['admin_email'],
                    'password' => Hash::make($generatedPassword),
                    'is_active' => true,
                    'user_type' => 'Contratista',
                ]);
                $user->roles()->attach(self::$cache['contratista_admin_role']);
                $this->passwords[] = ['email' => $user->email, 'password' => $generatedPassword];
            }

            // 1. Buscar la comuna y establecer el ID o null
            $comunaNombre = strtolower(trim($row['comuna'] ?? ''));
            $comunaId = !empty($comunaNombre) ? (self::$cache['comunas'][$comunaNombre] ?? null) : null;

            // 2. Construir el array de datos
            $dataContratista = [
                'razon_social' => $row['razon_social'],
                'nombre_fantasia' => !empty($row['nombre_comercial']) ? $row['nombre_comercial'] : 'S/D MIGRADO',
                'rut' => $rutEmpresa,
                'direccion_calle' => !empty($row['direccion_calle']) ? $row['direccion_calle'] : 'S/D MIGRADO',
                'direccion_numero' => !empty($row['direccion_numero']) ? (string) $row['direccion_numero'] : 'S/D MIGRADO',
                'comuna_id' => $comunaId,
                'telefono_empresa' => !empty($row['telefono_empresa']) ? (string) $row['telefono_empresa'] : 'S/D MIGRADO',
                'email_empresa' => !empty($row['email_empresa']) ? $row['email_empresa'] : null,
                'tipo_empresa_legal_id' => !empty($row['tipo_empresa_legal']) ? (self::$cache['tipos_empresa'][trim($row['tipo_empresa_legal'])] ?? null) : null,
                'rubro_id' => !empty($row['actividad_economica_rubro']) ? (self::$cache['rubros'][trim($row['actividad_economica_rubro'])] ?? null) : null,
                'rango_cantidad_trabajadores_id' => !empty($row['rango_empleados']) ? (self::$cache['rangos'][trim($row['rango_empleados'])] ?? null) : null,
                'mutualidad_id' => !empty($row['arl_mutualidad']) ? (self::$cache['mutualidades'][trim($row['arl_mutualidad'])] ?? null) : null,
                'admin_user_id' => $user->id,
                'rep_legal_nombres' => $row['rep_legal_nombres'],
                'rep_legal_apellido_paterno' => $row['rep_legal_primer_apellido'],
                'rep_legal_apellido_materno' => !empty($row['rep_legal_segundo_apellido']) ? $row['rep_legal_segundo_apellido'] : 'S/D MIGRADO',
                'rep_legal_rut' => $row['rep_legal_rut'],
                'rep_legal_telefono' => !empty($row['rep_legal_telefono']) ? (string) $row['rep_legal_telefono'] : 'S/D MIGRADO',
                'rep_legal_email' => $row['rep_legal_email'],
                'tipo_inscripcion' => 'Contratista',
                'is_active' => true,
                'estado_plataforma' => 'Activo',
            ];

            if ($contratista) {
                $dataContratista['updated_at'] = now();
                $contratista->update($dataContratista);
                $contratistaEnt = $contratista;
            } else {
                $dataContratista['created_at'] = now();
                $dataContratista['updated_at'] = now();
                $contratistaId = DB::table('contratistas')->insertGetId($dataContratista);
                $user->contratista_id = $contratistaId;
                $user->save();
                $contratistaEnt = ContratistaModel::find($contratistaId);
            }

            self::$cache['contratistas_creados'][$rutEmpresa] = $contratistaId;

            $mandanteId = self::$cache['mandantes'][trim($row['mandante_a_vincular_razon_social'])] ?? null;

            if ($nivelJerarquia > 0) {
                if (empty($rutPadre)) {
                    throw new \Exception("El RUT del Contratista Padre es obligatorio para subcontratistas (Nivel $nivelJerarquia).");
                }

                $padreId = self::$cache['contratistas_creados'][$rutPadre] ?? null;
                if (!$padreId) {
                    $padreDb = ContratistaModel::where('rut', $rutPadre)->first();
                    $padreId = $padreDb ? $padreDb->id : null;
                }
                
                if (!$padreId) {
                    throw new \Exception("RUT Contratista Padre ($rutPadre) no encontrado para el subcontratista nivel $nivelJerarquia.");
                }

                SolicitudVinculacion::firstOrCreate([
                    'contratista_id' => $contratistaId,
                    'mandante_id' => $mandanteId,
                    'tipo_solicitud' => 'SUBCONTRATISTA',
                    'contratista_padre_id' => $padreId,
                ], [
                    'estado' => 'APROBADA',
                    'aprobado_por_user_id' => $this->adminUserId,
                    'fecha_aprobacion' => now(),
                ]);
            } else {
                SolicitudVinculacion::firstOrCreate([
                    'contratista_id' => $contratistaId,
                    'mandante_id' => $mandanteId,
                    'tipo_solicitud' => 'CONTRATISTA',
                ], [
                    'estado' => 'APROBADA',
                    'aprobado_por_user_id' => $this->adminUserId,
                    'fecha_aprobacion' => now(),
                ]);
            }

            // Vincular U.O y Lugar de Trabajo Inicial con ID Registro
            $uoName = self::cleanCompositeName($row['nombre_uo_inicial'] ?? '');
            $depName = self::cleanCompositeName($row['nombre_lugar_de_trabajo_inicial'] ?? '');
            $idRegistro = trim($row['id_registro_opcional'] ?? '');
            $sap = trim($row['sap_opcional'] ?? '');
            $numeroContrato = trim($row['numero_de_contrato_inicial'] ?? '');

            $mId = (int) $mandanteId;
            $uoStr = (string) $uoName;
            $depStr = (string) $depName;

            $uo = null;
            if ($uoStr && $mId) {
                if (!isset(self::$cache['uo'][$mId])) {
                    $uos = \App\Models\UnidadOrganizacionalMandante::where('mandante_id', $mId)->get();
                    $uoMap = [];
                    foreach ($uos as $u) {
                        $uoMap[(string)$u->nombre_jerarquico] = $u;
                    }
                    self::$cache['uo'][$mId] = $uoMap;
                }
                $uo = self::$cache['uo'][$mId][$uoStr] ?? null;
            }

            $dep = null;
            if ($depStr && $mId) {
                if (!isset(self::$cache['dep'][$mId])) {
                    $deps = \App\Models\Dependencia::where('mandante_id', $mId)->get();
                    $depMap = [];
                    foreach ($deps as $d) {
                        $depMap[(string)$d->nombre_jerarquico] = $d;
                    }
                    self::$cache['dep'][$mId] = $depMap;
                }
                $dep = self::$cache['dep'][$mId][$depStr] ?? null;
            }

            if ($uo) {
                // Buscar si ya existe una vinculación de este contratista con este mandante (vía UO)
                $vinculacionExistente = \App\Models\ContratistaUnidadOrganizacional::where('contratista_id', $contratistaId)
                    ->whereHas('unidadOrganizacional', function($q) use ($mandanteId) {
                        $q->where('mandante_id', $mandanteId);
                    })
                    ->first();

                if (empty($idRegistro)) {
                    if ($vinculacionExistente && !empty($vinculacionExistente->id_registro)) {
                        $idRegistro = $vinculacionExistente->id_registro;
                    } else {
                        // Autogenerar: Buscar el máximo numérico para este Mandante
                        $lastIdRegistro = \App\Models\ContratistaUnidadOrganizacional::whereHas('unidadOrganizacional', function($q) use ($mandanteId) {
                                $q->where('mandante_id', $mandanteId);
                            })
                            ->whereRaw("id_registro REGEXP '^[0-9]+$'")
                            ->selectRaw("MAX(CAST(id_registro AS UNSIGNED)) as max_id")
                            ->value('max_id');
                        
                        $idRegistro = max((int) $lastIdRegistro, 40000) + 1;
                    }
                }

                if ($vinculacionExistente) {
                    // Actualizamos la vinculación existente para evitar duplicidad
                    $vinculacionExistente->update([
                        'unidad_organizacional_mandante_id' => $uo->id,
                        'id_registro' => $idRegistro,
                        'sap' => $sap ?: $vinculacionExistente->sap,
                        'numero_contrato' => $numeroContrato ?: $vinculacionExistente->numero_contrato,
                        'dependencia_id' => $dep ? $dep->id : $vinculacionExistente->dependencia_id,
                        'acredita' => true,
                        'verifica' => false,
                    ]);
                } else {
                    // Creamos una nueva vinculación si no existía ninguna con este mandante
                    $contratistaEnt->unidadesOrganizacionalesMandante()->attach($uo->id, [
                        'id_registro' => $idRegistro,
                        'sap' => $sap ?: null,
                        'numero_contrato' => $numeroContrato ?: null,
                        'dependencia_id' => $dep ? $dep->id : null,
                        'acredita' => true,
                        'verifica' => false,
                    ]);
                }
            }

            if ($dep) {
                if (!isset($contratistaEnt)) {
                    $contratistaEnt = ContratistaModel::find($contratistaId);
                }
                $contratistaEnt->dependencias()->syncWithoutDetaching([$dep->id]);
            }

            DB::commit();
            $this->successes++;
            
            return isset($contratistaEnt) ? $contratistaEnt : ContratistaModel::find($contratistaId);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->failures[] = [
                'row' => $this->getRowNumber(),
                'attribute' => 'General',
                'errors' => 'Error al procesar: ' . $e->getMessage(),
                'values' => $row,
            ];
            return null;
        }
    }

    public function rules(): array
    {
        return [
            // Eliminadas las reglas unique para permitir Upsert. La validación se hace en el try/catch.
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            if (empty(trim($failure->values()['razon_social'] ?? ''))) {
                continue;
            }
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
        $key = explode('.', $attribute)[1] ?? $attribute;
        return [
            'rut_empresa' => 'RUT Empresa',
            'email_empresa' => 'Email Empresa',
            'admin_email' => 'Admin. Email',
            'nombre_uo_inicial' => 'Nombre U.O. Inicial',
            'nombre_lugar_de_trabajo_inicial' => 'Nombre Lugar de Trabajo Inicial',
        ][$key] ?? $key;
    }

    public function chunkSize(): int
    {
        return 50;
    }
}