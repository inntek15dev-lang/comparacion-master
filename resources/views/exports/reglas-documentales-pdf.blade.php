<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Reglas Documentales</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 8px; color: #777; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #4f46e5; color: white; padding: 8px; text-align: left; text-transform: uppercase; }
        td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .badge { padding: 2px 5px; border-radius: 3px; font-weight: bold; }
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-inactive { background-color: #fee2e2; color: #991b1b; }
        .section-title { font-size: 12px; font-weight: bold; color: #4f46e5; margin-top: 15px; margin-bottom: 5px; }
        .detail-row { font-size: 9px; color: #555; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; color: #4f46e5;">REPORTE DE REGLAS DOCUMENTALES</h1>
        <p style="margin: 5px 0;">Generado el {{ $fecha }} por {{ $causer }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="20%">Principal / Entidad</th>
                <th width="35%">Documento / Atributos</th>
                <th width="30%">Aplicabilidad (Cargos/Nac.)</th>
                <th width="10%">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reglas as $regla)
                <tr>
                    <td>{{ $regla->id }}</td>
                    <td>
                        <strong>{{ $regla->mandante->razon_social ?? 'N/A' }}</strong><br>
                        <span style="color: #666;">{{ $regla->tipoEntidadControlada->nombre_entidad ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <strong>{{ $regla->nombreDocumento->nombre ?? 'N/A' }}</strong><br>
                        <div class="detail-row">
                            - Validez: {{ $regla->dias_validez_documento ?? 'Indefinida' }} días<br>
                            - Gracia: {{ $regla->dias_gracia_carga ?? 0 }} días<br>
                            - Vencimiento: {{ $regla->valida_vencimiento ? 'Sí' : 'No' }}
                        </div>
                    </td>
                    <td>
                        <span style="font-weight: bold; color: #444;">Cargos:</span><br>
                        {{ $regla->cargosAplica->pluck('nombre_cargo')->join(', ') ?: 'Todos' }}<br>
                        <span style="font-weight: bold; color: #444; margin-top: 5px; display: block;">Nacionalidades:</span><br>
                        {{ $regla->nacionalidadesAplica->pluck('nombre')->join(', ') ?: 'Todas' }}
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $regla->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $regla->is_active ? 'ACTIVA' : 'INACTIVA' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Página <span class="page-number"></span>
    </div>
</body>
</html>
