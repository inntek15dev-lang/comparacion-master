<?php

namespace App\Traits;

use ZipArchive;
use App\Models\CarpetaVerificacion;
use Illuminate\Support\Facades\Storage;

trait DescargaContextualTrait
{
    /**
     * @param array $carpetasIdArray
     * @param string $prefijoArchivo
     */
    public function procesarDescargaContextual($carpetasIdArray, $prefijoArchivo = "Auditorias")
    {
        if (empty($carpetasIdArray)) {
            session()->flash('error', 'No hay carpetas para descargar.');
            return;
        }

        // Cargamos las carpetas con sus relaciones y sus documentos
        $carpetas = CarpetaVerificacion::with([
            'vinculacion.contratista',
            'vinculacion.dependencia',
            'vinculacion.unidadOrganizacionalMandante.mandante',
            'documentos.requisito'
        ])
        ->whereIn('id', $carpetasIdArray)
        ->get();

        if ($carpetas->isEmpty()) {
            session()->flash('error', 'No se encontraron las carpetas seleccionadas.');
            return;
        }

        $zip = new ZipArchive();
        $zipFileName = 'Descarga_' . $prefijoArchivo . '_' . date('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $archivosAgregados = 0;
            $contadorArchivos = []; 

            $sanitizar = function(string $valor): string {
                $from = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'];
                $to   = ['a','e','i','o','u','u','n','A','E','I','O','U','U','N'];
                $valor = str_replace($from, $to, $valor);
                $valor = preg_replace('/[^A-Za-z0-9\-]/', '_', $valor);
                $valor = preg_replace('/_+/', '_', $valor);
                return strtoupper(trim($valor, '_'));
            };

            foreach ($carpetas as $carpeta) {
                $vinculacion = $carpeta->vinculacion;
                if (!$vinculacion) continue;

                $contratista = $vinculacion->contratista;
                if (!$contratista) continue;

                $mandanteRaw = $vinculacion->unidadOrganizacionalMandante?->mandante?->razon_social ?? 'SIN_PRINCIPAL';
                $mandante = $sanitizar($mandanteRaw);

                $lugarRaw = $vinculacion->dependencia?->nombre ?? 'SIN_LUGAR';
                $lugar = $sanitizar($lugarRaw);

                $contratoRaw = $vinculacion->numero_contrato ?: 'SC';
                $contrato = $sanitizar((string) $contratoRaw);

                $idRegistro = $vinculacion->id_registro ?: $contratista->id;
                $mesPad = str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT);
                $periodo = "{$mesPad}_{$carpeta->anio}";

                foreach ($carpeta->documentos as $doc) {
                    if (empty($doc->path)) continue;

                    // Buscar el archivo físico
                    $realPath = Storage::disk('local')->path($doc->path);
                    if (!file_exists($realPath)) {
                        $realPathPublic = Storage::disk('public')->path($doc->path);
                        if (file_exists($realPathPublic)) {
                            $realPath = $realPathPublic;
                        }
                    }

                    if (file_exists($realPath)) {
                        $requisitoGroup = $doc->requisito ? $sanitizar($doc->requisito->nombre) : 'OTROS_DOCUMENTOS';
                        $codigoDoc      = $doc->requisito ? ($doc->requisito->codigo ?: 'DOC') : 'DOC';

                        $keyContador = "{$requisitoGroup}_{$vinculacion->id}_{$periodo}";
                        if (!isset($contadorArchivos[$keyContador])) $contadorArchivos[$keyContador] = 1;
                        else $contadorArchivos[$keyContador]++;
                        $x = $contadorArchivos[$keyContador];

                        $ext = pathinfo($realPath, PATHINFO_EXTENSION);
                        if (!$ext && $doc->nombre_original) $ext = pathinfo($doc->nombre_original, PATHINFO_EXTENSION);
                        $ext = $ext ? strtoupper($ext) : 'PDF';

                        if ($doc->is_encrypted) {
                            try {
                                $contenido = app(\App\Services\EncryptionService::class)->decryptToMemory($doc->path);
                                // Forzamos la extensión a PDF porque el archivo decriptado es el original
                                $nombreArchivo = strtoupper("{$mandante}-{$idRegistro}-{$lugar}-{$contrato}-{$periodo}-{$codigoDoc}-{$x}.PDF");
                                $pathEnZip = $requisitoGroup . '/' . $nombreArchivo;
                                $zip->addFromString($pathEnZip, $contenido);
                                $archivosAgregados++;
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Error al desencriptar documento en ZIP masivo IA: " . $e->getMessage());
                                // Si falla la decriptación, no agregamos el archivo corrupto
                            }
                        } else {
                            $nombreArchivo = strtoupper("{$mandante}-{$idRegistro}-{$lugar}-{$contrato}-{$periodo}-{$codigoDoc}-{$x}.{$ext}");
                            $pathEnZip = $requisitoGroup . '/' . $nombreArchivo;
                            $zip->addFile($realPath, $pathEnZip);
                            $archivosAgregados++;
                        }
                    }
                }
            }
            $zip->close();
            
            if ($archivosAgregados > 0) {
                return response()->download($zipPath)->deleteFileAfterSend(true);
            } else {
                session()->flash('error', 'No se pudieron leer los archivos físicos en el servidor.');
                return;
            }
        } else {
            session()->flash('error', 'Error al crear el archivo ZIP.');
            return;
        }
    }
}
