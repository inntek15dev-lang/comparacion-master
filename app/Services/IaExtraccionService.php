<?php

namespace App\Services;

use App\Models\DocumentoCargado;
use App\Models\DatoExtraidoIa;
use App\Models\IaCampoConfiguracion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * IaExtraccionService — Extrae datos de un PDF enviándolo a OpenRouter / Gemini.
 *
 * Flujo:
 *  1. Lee el PDF (desencriptando si es necesario via EncryptionService)
 *  2. Construye el prompt desde ia_campos_configuracion de la regla
 *  3. Envía a OpenRouter como base64 + prompt
 *  4. Parsea la respuesta JSON
 *  5. Guarda (o actualiza) en datos_extraidos_ia
 *  6. Llama a IaMatchService para calcular el match automáticamente
 */
class IaExtraccionService
{
    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly IaMatchService    $matchService,
    ) {}

    /**
     * Procesa un documento: extrae datos via API y calcula el match.
     *
     * @throws \RuntimeException si el doc no tiene archivo o no hay campos configurados
     */
    public function procesarDocumento(DocumentoCargado $documento, ?string $modelOverride = null): DatoExtraidoIa
    {
        // 1. Verificar que haya archivo
        if (!$documento->ruta_archivo) {
            throw new \RuntimeException("El documento ID {$documento->id} no tiene archivo asociado.");
        }

        // 2. Cargar campos configurados
        $campos = IaCampoConfiguracion::where('regla_documental_id', $documento->regla_documental_id_origen)
            ->where('is_active', true)
            ->orderBy('orden')
            ->get();

        if ($campos->isEmpty()) {
            throw new \RuntimeException(
                "La regla documental ID {$documento->regla_documental_id_origen} no tiene campos IA configurados."
            );
        }

        // 3. Leer el PDF en memoria (desencriptando si aplica)
        $pdfContent = $this->leerPdf($documento);
        $pdfBase64  = base64_encode($pdfContent);

        // 4. Construir el prompt
        $prompt = $this->construirPrompt($campos, $documento);

        // 4.5. Extraer formatos de muestra base64 (si hay)
        $formatosBase64 = [];
        $formatosIds = $campos->pluck('formato_muestra_id')->filter()->unique();
        if ($formatosIds->isNotEmpty()) {
            $formatos = \App\Models\FormatoDocumentoMuestra::whereIn('id', $formatosIds)->get();
            foreach ($formatos as $formato) {
                if ($formato->ruta_archivo && \Illuminate\Support\Facades\Storage::disk('public')->exists($formato->ruta_archivo)) {
                    $formatoContent = \Illuminate\Support\Facades\Storage::disk('public')->get($formato->ruta_archivo);
                    $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($formato->ruta_archivo);
                    $formatosBase64[] = [
                        'filename' => $formato->nombre_archivo_original ?? 'formato.jpg',
                        'mime'     => $mime ?: 'image/jpeg',
                        'base64'   => base64_encode($formatoContent),
                        'nombre'   => $formato->nombre,
                    ];
                }
            }
        }

        // 5. Llamar a OpenRouter
        $respuestaRaw = $this->llamarOpenRouter($pdfBase64, $prompt, $modelOverride, $formatosBase64);

        // 6. Parsear JSON de la respuesta
        $datosExtraidos = $this->parsearRespuesta($respuestaRaw, $campos);

        // 7. Guardar o actualizar datos_extraidos_ia
        $datoIa = $this->guardarDatoIa($documento, $respuestaRaw, $datosExtraidos);

        // 8. Omitimos cálculo de match automático a petición del admin. El match será disparado manualmente con el botón "Aceptar IA".
        // $this->matchService->calcularMatch($datoIa);

        return $datoIa;
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function leerPdf(DocumentoCargado $documento): string
    {
        if ($documento->is_encrypted) {
            // Archivo encriptado en disk:local
            return $this->encryptionService->decryptToMemory($documento->ruta_archivo);
        }

        // Archivo plano en disk:public (legado)
        if (!Storage::disk('public')->exists($documento->ruta_archivo)) {
            throw new \RuntimeException("Archivo no encontrado en storage: {$documento->ruta_archivo}");
        }

        return Storage::disk('public')->get($documento->ruta_archivo);
    }

    private function construirPrompt(\Illuminate\Support\Collection $campos, DocumentoCargado $documento): string
    {
        $listaCampos = $campos->map(function ($campo) {
            $def  = \App\Services\IaCamposDisponibles::definicion($campo->campo_clave);
            $tipo = $def ? ($def['tipo_dato'] ?? $campo->tipo_dato) : $campo->tipo_dato;
            $hint = $def ? ($def['descripcion'] ?? $campo->descripcion_ia ?? '') : ($campo->descripcion_ia ?? '');
            $etiqueta = $def ? ($def['etiqueta'] ?? $campo->etiqueta) : $campo->etiqueta;
            
            $formatoNota = $campo->formato_muestra_id ? " (Evalúa esto comparándolo con las imágenes de 'Formato de Muestra' adjuntas)" : "";
            
            if ($campo->esCriterio()) {
                return "- {$campo->campo_clave}_extraido: Extrae textualmente los datos del documento que el humano te pidió buscar en esta instrucción: {$hint}\n" .
                       "- {$campo->campo_clave}_cumple: Actúa como auditor estricto. Revisa si el documento cumple con la regla '{$etiqueta}'. Responde ÚNICA Y EXCLUSIVAMENTE con 'SI' o 'NO'.{$formatoNota}";
            }
            
            return "- {$campo->campo_clave} (tipo: {$tipo}): {$etiqueta} — {$hint}{$formatoNota}";
        })->implode("\n");

        $formatoEsperadoArray = [];
        foreach ($campos as $c) {
            if ($c->esCriterio()) {
                $formatoEsperadoArray[$c->campo_clave . '_extraido'] = "texto extraido";
                $formatoEsperadoArray[$c->campo_clave . '_cumple'] = "SI o NO";
            } else {
                $formatoEsperadoArray[$c->campo_clave] = "valor";
            }
        }
        $formatoEsperado = json_encode($formatoEsperadoArray, JSON_PRETTY_PRINT);

        $entidad = $documento->entidad;
        $contextoEntidad = '';
        if ($entidad) {
            $contextoEntidad .= "DATOS DE REFERENCIA DEL SISTEMA (Usa esta información para validar si el documento corresponde a la persona/empresa/vehículo):\n";
            if ($documento->entidad_type === 'App\Models\Trabajador') {
                $contextoEntidad .= "- TIPO DE ENTIDAD: Trabajador\n";
                $contextoEntidad .= "- NOMBRE COMPLETO: " . trim(($entidad->nombres ?? '') . ' ' . ($entidad->apellidos ?? '')) . "\n";
                $contextoEntidad .= "- RUT/IDENTIFICADOR: " . ($entidad->rut ?? '') . "\n";
                if ($entidad->nacionalidad) {
                    $contextoEntidad .= "- NACIONALIDAD: " . ($entidad->nacionalidad->nombre ?? '') . "\n";
                }
                if ($entidad->tipoPermanencia) {
                    $contextoEntidad .= "- TIPO DE PERMANENCIA: " . ($entidad->tipoPermanencia->nombre ?? '') . "\n";
                }
                
                $vinc = $documento->trabajadorVinculacion;
                if (!$vinc) {
                    $vinc = $entidad->vinculaciones()->where('is_active', true)->first();
                }
                if ($vinc && $vinc->cargoMandante) {
                    $contextoEntidad .= "- CARGO: " . ($vinc->cargoMandante->nombre_cargo ?? '') . "\n";
                }
            } elseif ($documento->entidad_type === 'App\Models\Vehiculo') {
                $contextoEntidad .= "- TIPO DE ENTIDAD: Vehículo\n";
                $contextoEntidad .= "- PATENTE: " . trim(($entidad->patente_letras ?? '') . ($entidad->patente_numeros ?? '')) . "\n";
                $contextoEntidad .= "- MARCA: " . ($entidad->marca ?? '') . "\n";
                $contextoEntidad .= "- MODELO: " . ($entidad->modelo ?? '') . "\n";
            } elseif ($documento->entidad_type === 'App\Models\Empresa' || $documento->entidad_type === 'App\Models\Contratista') {
                $contextoEntidad .= "- TIPO DE ENTIDAD: Empresa / Contratista\n";
                $contextoEntidad .= "- RAZON SOCIAL: " . ($entidad->razon_social ?? '') . "\n";
                $contextoEntidad .= "- RUT/IDENTIFICADOR: " . ($entidad->rut ?? '') . "\n";
            }
            $contextoEntidad .= "\n";
        }

        return <<<PROMPT
Eres un asistente especializado en extracción de datos de documentos laborales y empresariales.
Analiza el documento PDF (la primera imagen/archivo adjunto). Si hay más imágenes adjuntas, son FORMATOS DE MUESTRA para comparar visualmente.
Extrae EXACTAMENTE los campos solicitados basándote en la información del documento principal.
Responde ÚNICAMENTE con un objeto JSON válido, sin texto adicional, sin markdown, sin explicaciones.

{$contextoEntidad}CAMPOS A EXTRAER:
{$listaCampos}

REGLAS IMPORTANTES:
- Si un campo no se encuentra en el documento, devuelve null para ese campo.
- Para campos tipo "rut": incluye puntos y guión (ej: 12.345.678-9).
- Para campos tipo "fecha": usa formato YYYY-MM-DD.
- Para campos tipo "texto" con formato YYYY-MM: extrae el período mensual.
- Para campos tipo "numero": solo números.
- PROHIBIDO EXTRAER DATOS (rut, nombres, fechas) DE LOS FORMATOS DE MUESTRA. Los formatos de muestra son solo referencias visuales, no son el documento del trabajador. Todo dato real debe salir del primer documento adjunto.

FORMATO DE RESPUESTA (JSON exacto, sin nada más):
{$formatoEsperado}
PROMPT;
    }

    private function llamarOpenRouter(string $pdfBase64, string $prompt, ?string $modelOverride = null, array $formatosBase64 = []): ?array
    {
        $apiKey  = config('services.openrouter.api_key');
        $baseUrl = config('services.openrouter.base_url');
        $model   = $modelOverride ?: config('services.openrouter.model');

        if (empty($apiKey)) {
            throw new \RuntimeException('OPENROUTER_API_KEY no está configurada en .env');
        }

        $contentArr = [
            [
                'type' => 'text',
                'text' => $prompt,
            ],
            [
                'type' => 'file',
                'file' => [
                    'filename' => 'documento.pdf',
                    'file_data'=> "data:application/pdf;base64,{$pdfBase64}",
                ],
            ],
        ];

        // Añadir los formatos de muestra como imágenes/archivos adicionales
        foreach ($formatosBase64 as $formato) {
            $contentArr[] = [
                'type' => 'file',
                'file' => [
                    'filename' => $formato['filename'],
                    'file_data'=> "data:{$formato['mime']};base64,{$formato['base64']}",
                ],
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
            'HTTP-Referer'  => config('app.url'),
            'X-Title'       => 'OvalControl IA Acreditación',
        ])->timeout(120)->post("{$baseUrl}/chat/completions", [
            'model' => $model,
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => $contentArr,
                ],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            Log::error('[IaExtraccionService] Error OpenRouter: ' . $response->body());
            throw new \RuntimeException('Error al consultar la API de IA: ' . $response->status() . ' — ' . $response->body());
        }

        return $response->json();
    }

    private function parsearRespuesta(?array $respuestaRaw, \Illuminate\Support\Collection $campos): array
    {
        if (!is_array($respuestaRaw) || !isset($respuestaRaw['choices'][0]['message']['content'])) {
            Log::warning('[IaExtraccionService] Respuesta sin formato válido o vacía: ' . json_encode($respuestaRaw));
            return [];
        }

        $content = $respuestaRaw['choices'][0]['message']['content'];

        // Limpiar posible markdown ```json ... ```
        $content = preg_replace('/^```json\s*/m', '', $content);
        $content = preg_replace('/^```\s*/m', '', $content);
        $content = trim($content);

        $datos = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('[IaExtraccionService] JSON inválido en respuesta: ' . $content);
            return [];
        }

        // Asegurar que solo vengan las claves configuradas (y sus sufijos si son criterios)
        $clavesPermitidas = [];
        foreach ($campos as $c) {
            if ($c->esCriterio()) {
                $clavesPermitidas[] = $c->campo_clave . '_extraido';
                $clavesPermitidas[] = $c->campo_clave . '_cumple';
            } else {
                $clavesPermitidas[] = $c->campo_clave;
            }
        }
        
        return array_intersect_key($datos, array_flip($clavesPermitidas));
    }

    private function guardarDatoIa(
        DocumentoCargado $documento,
        ?array $respuestaRaw,
        array $datosExtraidos
    ): DatoExtraidoIa {
        $usage = is_array($respuestaRaw) ? ($respuestaRaw['usage'] ?? []) : [];

        $atributos = [
            'fuente'              => 'API',
            'proveedor_ia'        => config('services.openrouter.model'),
            'datos_extraidos'     => $datosExtraidos,
            'respuesta_cruda_ia'  => $respuestaRaw,
            'tokens_entrada'      => $usage['prompt_tokens'] ?? null,
            'tokens_salida'       => $usage['completion_tokens'] ?? null,
            'costo_estimado_usd'  => null, // OpenRouter no siempre lo devuelve en el sync response
            'match_calculado'     => null,
            'detalle_match'       => null,
            'observacion_match'   => null,
            'estado'              => 'EXTRAIDO',
            'usuario_confirma_id' => null,
            'fecha_confirmacion'  => null,
        ];

        // updateOrCreate → actualiza si ya existe (reprocesamiento), crea si no
        return DatoExtraidoIa::updateOrCreate(
            ['documento_cargado_id' => $documento->id],
            $atributos
        );
    }
}
