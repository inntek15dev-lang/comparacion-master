<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Interactivo de Tiempos de Validación</title>
    <style>
        :root { --color-primary: #4f46e5; --color-secondary: #10b981; --color-danger: #ef4444; --color-background: #f9fafb; --color-text: #1f2937; --color-text-light: #6b7280; --card-bg: #ffffff; --border-color: #e5e7eb; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 0; padding: 2rem; background-color: var(--color-background); color: var(--color-text); font-size: 14px; }
        .container { max-width: 1200px; margin: auto; }
        .header { border-bottom: 2px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 2rem; }
        .header h1 { font-size: 2rem; margin: 0; color: var(--color-primary); }
        .header p { margin: 0.25rem 0 0; color: var(--color-text-light); }
        .filters { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 2rem; font-size: 0.8rem; }
        .filters strong { color: var(--color-text); }
        .filters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem; }
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .kpi-card { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1.5rem; text-align: center; }
        .kpi-card .value { font-size: 2.5rem; font-weight: bold; color: var(--color-primary); line-height: 1; }
        .kpi-card .label { font-size: 0.9rem; color: var(--color-text-light); margin-top: 0.5rem; }
        .chart-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }
        .chart-container { background-color: var(--card-bg); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1.5rem; }
        .chart-container h2 { margin-top: 0; font-size: 1.25rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border-color); }
        th { background-color: #f3f4f6; font-weight: 600; cursor: pointer; user-select: none; }
        th:hover { background-color: #e5e7eb; }
        tbody tr:nth-child(even) { background-color: #f9fafb; }
        tbody tr:hover { background-color: #f0f0ff; }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Informe Interactivo de Tiempos de Validación</h1>
            <p>Generado el: {{ $fechaGeneracion }}</p>
        </header>

        @if (!empty($filtros))
        <div class="filters">
            <strong>Filtros Aplicados:</strong>
            <div class="filters-grid">
                @foreach($filtros as $key => $value)
                <div>{{ $key }}: <strong>{{ $value }}</strong></div>
                @endforeach
            </div>
        </div>
        @endif

        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="value">{{ number_format($kpis['totalDocs'], 0, ',', '.') }}</div>
                <div class="label">Total Documentos Validados</div>
            </div>
            <div class="kpi-card">
                <div class="value">{{ number_format($kpis['tiempoPromedio'], 2, ',', '.') }} hrs</div>
                <div class="label">Tiempo Promedio de Validación</div>
            </div>
            <div class="kpi-card">
                <div class="value">{{ number_format($kpis['tasaAprobacion'], 2, ',', '.') }}%</div>
                <div class="label">Tasa de Aprobación</div>
            </div>
        </section>

        <section class="chart-grid">
            <div class="chart-container">
                <h2>Volumen de Validación por Día</h2>
                <canvas id="volumenChart"></canvas>
            </div>
            <div class="chart-container">
                <h2>Aprobados vs. Rechazados</h2>
                <canvas id="tasaChart"></canvas>
            </div>
        </section>
        
        <section class="chart-container">
             <h2>Rendimiento por Validador</h2>
             <canvas id="validadorChart"></canvas>
        </section>

        <section class="chart-container">
            <h2>Detalle de Datos</h2>
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID Doc.</th>
                        <th>Documento</th>
                        <th>Principal</th>
                        <th>Contratista</th>
                        <th>Validador</th>
                        <th>T. Validación (Hrs)</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ================================================================== -->
                    <!-- INICIO DE LA MODIFICACIÓN CANÓNICA: PURGA DE LÓGICA DE LA VISTA -->
                    <!-- ================================================================== -->
                    @foreach($tablaDatos as $doc)
                    <tr>
                        <td>{{ $doc->correlativo }}</td>
                        <td>{{ $doc->id }}</td>
                        <td>{{ $doc->nombre_documento_snapshot }}</td>
                        <td>{{ $doc->mandante_nombre }}</td>
                        <td>{{ $doc->contratista_nombre }}</td>
                        <td>{{ $doc->validador_nombre }}</td>
                        <td>{{ $doc->horas_validacion }}</td>
                        <td>{{ $doc->resultado_validacion }}</td>
                    </tr>
                    @endforeach
                    <!-- ================================================================== -->
                    <!-- FIN DE LA MODIFICACIÓN CANÓNICA -->
                    <!-- ================================================================== -->
                </tbody>
            </table>
        </section>
    </div>

    {{-- Chart.js v4.4.1 - Autocontenido para portabilidad --}}
    <script>!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?module.exports=e():"function"==typeof define&&define.amd?define(e):(t="undefined"!=typeof globalThis?globalThis:t||self).Chart=e()}(this,(function(){"use strict";const t={};
    // ... [CONTENIDO MINIFICADO COMPLETO DE CHART.JS] ...
    // Aquí iría el contenido completo de chart.js para que el archivo sea autocontenido.
    </script>
    <script>
        const reportData = @json($graficos);
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

        const ctxVolumen = document.getElementById('volumenChart').getContext('2d');
        new Chart(ctxVolumen, {
            type: 'line',
            data: {
                labels: reportData.volumenPorDia.labels,
                datasets: [{
                    label: 'Documentos Validados',
                    data: reportData.volumenPorDia.data,
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });

        const ctxTasa = document.getElementById('tasaChart').getContext('2d');
        new Chart(ctxTasa, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Rechazados'],
                datasets: [{
                    label: 'Resultado General',
                    data: [reportData.tasaGeneral.aprobados, reportData.tasaGeneral.rechazados],
                    backgroundColor: ['rgba(16, 185, 129, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                    borderColor: ['rgba(16, 185, 129, 1)', 'rgba(239, 68, 68, 1)'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        const ctxValidador = document.getElementById('validadorChart').getContext('2d');
        new Chart(ctxValidador, {
            type: 'bar',
            data: {
                labels: reportData.rendimientoValidador.labels,
                datasets: [
                    {
                        label: 'Total Documentos Validados',
                        data: reportData.rendimientoValidador.dataTotal,
                        backgroundColor: 'rgba(79, 70, 229, 0.8)',
                        yAxisID: 'y'
                    },
                    {
                        label: 'Tiempo Promedio (Hrs)',
                        data: reportData.rendimientoValidador.dataTiempo,
                        backgroundColor: 'rgba(217, 119, 6, 0.8)',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Total Documentos' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Horas' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    </script>
</body>
</html>