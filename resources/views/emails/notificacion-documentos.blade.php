<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Documentos</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 90%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #f4f4f4; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { margin-top: 20px; }
        .message-block { background-color: #f9f9f9; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .footer { margin-top: 20px; font-size: 0.9em; text-align: center; color: #777; }
        .section-title { font-size: 1.2em; font-weight: bold; color: #c0392b; border-bottom: 2px solid #c0392b; padding-bottom: 5px; margin-top: 25px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Notificación de Estado de Documentación</h2>
        </div>
        <div class="content">
            <p>Estimado/a <strong>{{ $contratista->razon_social }}</strong>,</p>

            {{-- ================== INICIO: BLOQUE DE MENSAJE PERSONALIZADO ================== --}}
            <div class="message-block">
                {!! nl2br(e($mensajePersonalizado)) !!}
            </div>
            {{-- =================== FIN: BLOQUE DE MENSAJE PERSONALIZADO ==================== --}}

            @if($documentosVencidos->isNotEmpty())
                <h3 class="section-title">Detalle de Documentos Vencidos</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Entidad Afectada</th>
                            <th>Fecha de Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documentosVencidos as $doc)
                            <tr>
                                <td>{{ $doc->nombre_documento_snapshot }}</td>
                                <td>{{ class_basename($doc->entidad_type) }}: {{ $doc->entidad->nombre_completo ?? $doc->entidad->identificador_completo ?? $doc->entidad->rut }}</td>
                                <td>{{ $doc->fecha_vencimiento ? $doc->fecha_vencimiento->format('d-m-Y') : 'N/A' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if($documentosRechazados->isNotEmpty())
                <h3 class="section-title">Detalle de Documentos Rechazados</h3>
                 <table class="table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Entidad Afectada</th>
                            <th>Observación de Rechazo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documentosRechazados as $doc)
                            <tr>
                                <td>{{ $doc->nombre_documento_snapshot }}</td>
                                <td>{{ class_basename($doc->entidad_type) }}: {{ $doc->entidad->nombre_completo ?? $doc->entidad->identificador_completo ?? $doc->entidad->rut }}</td>
                                <td>{{ Str::limit($doc->observacion_rechazo, 100) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <p style="margin-top: 25px;">
                Para gestionar estos documentos, por favor ingrese a nuestra plataforma.
            </p>
        </div>
        <div class="footer">
            <p>Este es un correo electrónico generado automáticamente. Por favor, no responda a este mensaje.</p>
        </div>
    </div>
</body>
</html>