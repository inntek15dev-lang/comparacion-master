<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Supervisión de Cumplimiento</title>
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
        .text-orange { color: #e67e22; }
        .text-green { color: #27ae60; }
        .rut { font-family: monospace; }
        .chart-container { margin-top: 40px; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .chart-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; }
        /* Estilos para PDF */
        @page { margin: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Supervisión de Cumplimiento</h1>
        <p>Generado para: {{ $mandanteNombre }}</p>
        <p>Fecha de Generación: {{ now()->format('d-m-Y H:i:s') }}</p>
        @if($filtros)
            <p>Filtros Aplicados: Contratista "{{ $filtros }}"</p>
        @endif
    </div>

    <table class="report-table">
        <thead>
            <tr>
                <th>Contratista</th>
                {{-- <<< INICIO DE LA MODIFICACIÓN CANÓNICA: CABECERAS DINÁMICAS >>> --}}
                @if(in_array('EMPRESA', $entidadesControlables))
                <th class="text-center">Empresa (%)</th>
                @endif
                @if(in_array('PERSONA', $entidadesControlables))
                <th class="text-center">Trabajadores (% / Total)</th>
                @endif
                @if(in_array('VEHICULO', $entidadesControlables))
                <th class="text-center">Vehículos (% / Total)</th>
                @endif
                @if(in_array('MAQUINARIA', $entidadesControlables))
                <th class="text-center">Maquinaria (% / Total)</th>
                @endif
                @if(in_array('EMBARCACION', $entidadesControlables))
                <th class="text-center">Embarcaciones (% / Total)</th>
                @endif
                {{-- <<< FIN DE LA MODIFICACIÓN CANÓNICA >>> --}}
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td>
                        {{ $item['razon_social'] }}
                        <span class="rut"><br>{{ $item['rut'] }}</span>
                    </td>
                    {{-- <<< INICIO DE LA MODIFICACIÓN CANÓNICA: CELDAS DINÁMICAS >>> --}}
                    @if(in_array('EMPRESA', $entidadesControlables))
                    <td class="text-center">
                        @if(isset($item['cumplimiento_empresa']))
                        <span class="{{ $item['cumplimiento_empresa'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['cumplimiento_empresa'] }}%</span>
                        @endif
                    </td>
                    @endif
                    @if(in_array('PERSONA', $entidadesControlables))
                    <td class="text-center">
                        @if(isset($item['promedio_trabajadores']))
                        <span class="{{ $item['promedio_trabajadores']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_trabajadores']['promedio'] }}%</span>
                        <span> ({{ $item['promedio_trabajadores']['total'] }})</span>
                        @endif
                    </td>
                    @endif
                    @if(in_array('VEHICULO', $entidadesControlables))
                    <td class="text-center">
                        @if(isset($item['promedio_vehiculos']))
                        <span class="{{ $item['promedio_vehiculos']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_vehiculos']['promedio'] }}%</span>
                        <span> ({{ $item['promedio_vehiculos']['total'] }})</span>
                        @endif
                    </td>
                    @endif
                    @if(in_array('MAQUINARIA', $entidadesControlables))
                    <td class="text-center">
                        @if(isset($item['promedio_maquinarias']))
                        <span class="{{ $item['promedio_maquinarias']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_maquinarias']['promedio'] }}%</span>
                        <span> ({{ $item['promedio_maquinarias']['total'] }})</span>
                        @endif
                    </td>
                    @endif
                    @if(in_array('EMBARCACION', $entidadesControlables))
                    <td class="text-center">
                        @if(isset($item['promedio_embarcaciones']))
                        <span class="{{ $item['promedio_embarcaciones']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_embarcaciones']['promedio'] }}%</span>
                        <span> ({{ $item['promedio_embarcaciones']['total'] }})</span>
                        @endif
                    </td>
                    @endif
                    {{-- <<< FIN DE LA MODIFICACIÓN CANÓNICA >>> --}}
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 1 + count($entidadesControlables) }}" class="text-center">No hay datos para mostrar con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!$isPdf)
        <div class="chart-container">
            <h3 class="chart-title">Gráfico Comparativo de Cumplimiento por Entidad</h3>
            <canvas id="complianceChart"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const chartData = @json($chartData);
            const ctx = document.getElementById('complianceChart');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%'
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + '%';
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        </script>
    @endif
</body>
</html>