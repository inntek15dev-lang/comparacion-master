<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Producción de Validadores</title>
    <style>
        body { font-family: sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2c3e50; }
        .header p { margin: 5px 0; color: #7f8c8d; font-size: 12px; }
        .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .report-table th, .report-table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        .report-table th { background-color: #f2f2f2; font-weight: bold; }
        .report-table tr:nth-child(even) { background-color: #f9f9f9; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-red { color: #c0392b; }
        .total-row td { font-weight: bold; background-color: #ecf0f1; }
        @page { margin: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Informe de Producción de Validadores</h1>
        <p>Fecha de Generación: {{ now()->format('d-m-Y H:i:s') }}</p>
        @if($filtros['fecha_desde'] && $filtros['fecha_hasta'])
            <p>Periodo de Validación: <strong>{{ \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d-m-Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d-m-Y') }}</strong></p>
        @endif
        @if($filtros['documento'])
            <p>Filtro de Documento: <strong>{{ $filtros['documento'] }}</strong></p>
        @endif
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>Validador</th>
                <th class="text-center">Rol</th>
                <th class="text-right">Total Revisados</th>
                <th class="text-right">Aprobados</th>
                <th class="text-right">Rechazados</th>
                <th class="text-right">Errores (*)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($datos as $item)
                <tr>
                    <td>{{ $item->validador_nombre }}</td>
                    <td class="text-center">{{ $item->rol }}</td>
                    <td class="text-right">{{ $item->total_revisados }}</td>
                    <td class="text-right">{{ $item->aprobados }}</td>
                    <td class="text-right">{{ $item->rechazados }}</td>
                    <td class="text-right @if($item->errores > 0) text-red @endif">{{ $item->errores }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No hay datos de producción para los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">TOTALES GENERALES</td>
                <td class="text-right">{{ $datos->sum('total_revisados') }}</td>
                <td class="text-right">{{ $datos->sum('aprobados') }}</td>
                <td class="text-right">{{ $datos->sum('rechazados') }}</td>
                <td class="text-right @if($datos->sum('errores') > 0) text-red @endif">{{ $datos->sum('errores') }}</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>