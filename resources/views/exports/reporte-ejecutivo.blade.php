<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Ejecutivo de Cumplimiento</title>
    <script>
        {{-- Chart.js v4.4.1 minificado y embebido para portabilidad --}}
        !function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).Chart=e()}(this,(function(){"use strict";
        // ... (CÓDIGO MINIFICADO DE CHART.JS VA AQUÍ - ES MUY LARGO PARA MOSTRARLO, PERO SE INCLUIRÍA EN LA IMPLEMENTACIÓN REAL)
        // Por simplicidad, lo llamaremos desde un CDN, pero en producción se embebería.
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; margin: 0; background-color: #f4f7f9; color: #333; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 8px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #e0e0e0; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #1a202c; font-size: 24px; }
        .header p { margin: 5px 0; color: #718096; font-size: 14px; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-bottom: 30px; }
        .chart-card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; }
        .chart-card.full-width { grid-column: 1 / -1; }
        .chart-title { font-size: 18px; font-weight: 600; margin-bottom: 15px; color: #2d3748; }
        .insights { border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; font-size: 14px; line-height: 1.6; }
        .insights ul { padding-left: 20px; margin: 0; }
        .report-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .report-table th, .report-table td { border: 1px solid #e2e8f0; padding: 12px; text-align: left; font-size: 14px; }
        .report-table thead { background-color: #edf2f7; }
        .report-table th { font-weight: 600; color: #4a5568; }
        .report-table tbody tr:hover { background-color: #f7fafc; }
        .total-row { background-color: #2d3748; color: #fff; font-weight: bold; }
        .total-row td { border-color: #2d3748; }
        .avg-box { background-color: #e0f2fe; color: #0c4a6e; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: bold; display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reporte Ejecutivo de Cumplimiento</h1>
            <p>Generado para: {{ $mandanteNombre }}</p>
            <p>Fecha de Generación: {{ now()->format('d-m-Y H:i:s') }}</p>
        </div>

        <div class="grid">
            @if($datosGraficos['empresa'])
            <div class="chart-card">
                <div class="chart-title">Cumplimiento Empresa</div>
                <canvas id="chartEmpresa"></canvas>
                <div class="avg-box">Promedio Cumplimiento: {{ $promediosGenerales['cumplimiento_empresa'] }}%</div>
            </div>
            @endif
            @if($datosGraficos['trabajador'])
            <div class="chart-card">
                <div class="chart-title">Cumplimiento Trabajador</div>
                <canvas id="chartTrabajador"></canvas>
                <div class="avg-box">Promedio Cumplimiento: {{ $promediosGenerales['promedio_trabajadores'] }}%</div>
            </div>
            @endif
            <div class="chart-card full-width">
                <div class="chart-title">Cumplimiento Total por Contexto</div>
                <canvas id="chartTotal"></canvas>
                <div class="avg-box">Promedio Cumplimiento: {{ $promediosGenerales['cumplimiento_total'] }}%</div>
            </div>
        </div>

        <div class="insights">
            <ul>
                @foreach($insights as $insight)
                    <li>{{ $insight }}</li>
                @endforeach
            </ul>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    {{-- ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ================== --}}
                    <th>Contexto Operativo</th>
                    {{-- ================== FIN DE LA MODIFICACIÓN DOCTRINAL ==================== --}}
                    @if(in_array('EMPRESA', $entidadesControlables))<th>Promedio de Cump. Emp ({{$promediosGenerales['cumplimiento_empresa']}}%)</th>@endif
                    @if(in_array('PERSONA', $entidadesControlables))<th>Promedio de Cump. Trab ({{$promediosGenerales['promedio_trabajadores']}}%)</th>@endif
                    <th>Promedio de Cump. Total ({{$promediosGenerales['cumplimiento_total']}}%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datosTabla as $item)
                <tr>
                    {{-- ================== INICIO DE LA MODIFICACIÓN DOCTRINAL ================== --}}
                    <td>{{ $item['etiqueta_contextual'] }}</td>
                    {{-- ================== FIN DE LA MODIFICACIÓN DOCTRINAL ==================== --}}
                    @if(in_array('EMPRESA', $entidadesControlables))<td>{{ $item['cumplimiento_empresa'] ?? 'N/A' }}%</td>@endif
                    @if(in_array('PERSONA', $entidadesControlables))<td>{{ $item['promedio_trabajadores']['promedio'] ?? 'N/A' }}%</td>@endif
                    <td>{{ $item['cumplimiento_total'] }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Promedio General</td>
                    @if(in_array('EMPRESA', $entidadesControlables))<td>{{ $promediosGenerales['cumplimiento_empresa'] }}%</td>@endif
                    @if(in_array('PERSONA', $entidadesControlables))<td>{{ $promediosGenerales['promedio_trabajadores'] }}%</td>@endif
                    <td>{{ $promediosGenerales['cumplimiento_total'] }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const createChart = (ctx, title, labels, data) => {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: title,
                        data: data,
                        backgroundColor: '#3b82f6',
                        borderColor: '#1e40af',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true, max: 100 } }
                }
            });
        };

        @if($datosGraficos['empresa'])
            createChart(document.getElementById('chartEmpresa'), 'Cumplimiento Empresa', @json($datosGraficos['empresa']['labels']), @json($datosGraficos['empresa']['data']));
        @endif
        @if($datosGraficos['trabajador'])
            createChart(document.getElementById('chartTrabajador'), 'Cumplimiento Trabajador', @json($datosGraficos['trabajador']['labels']), @json($datosGraficos['trabajador']['data']));
        @endif
        createChart(document.getElementById('chartTotal'), 'Cumplimiento Total', @json($datosGraficos['total']['labels']), @json($datosGraficos['total']['data']));
    </script>
</body>
</html>