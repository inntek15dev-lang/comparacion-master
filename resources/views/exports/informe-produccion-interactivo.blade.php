<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Interactivo de Producción</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 20px; color: #333; background-color: #f4f7f9; }
        .container { max-width: 1600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2c3e50; }
        .header p { margin: 5px 0; color: #7f8c8d; font-size: 12px; }
        .filter-panel { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #dee2e6; }
        .filter-panel h3 { margin-top: 0; font-size: 16px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; max-height: 150px; overflow-y: auto; }
        .filter-grid label { display: flex; align-items: center; font-size: 12px; }
        .filter-grid input { margin-right: 8px; }
        .validador-section { margin-bottom: 30px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
        .validador-header { background-color: #34495e; color: white; padding: 10px 15px; font-size: 18px; font-weight: bold; }
        .validador-content { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 15px; }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table th, .report-table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
        .report-table th { background-color: #f2f2f2; font-weight: bold; }
        .report-table tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .text-red { color: #c0392b; font-weight: bold; }
        .chart-container { display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Dashboard Interactivo de Producción</h1>
            <p>Fecha de Generación: {{ now()->format('d-m-Y H:i:s') }}</p>
            @if($filtros['fecha_desde'] && $filtros['fecha_hasta'])
                <p>Periodo de Validación: <strong>{{ \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d-m-Y') }}</strong> al <strong>{{ \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d-m-Y') }}</strong></p>
            @endif
            @if($filtros['documento'])
                <p>Filtro de Documento: <strong>{{ $filtros['documento'] }}</strong></p>
            @endif
        </div>

        @if(!$datosGranulares->isEmpty())
            <div class="filter-panel">
                <h3>Filtrar Documentos en el Reporte:</h3>
                <div id="document-filter-grid" class="filter-grid"></div>
            </div>
        @endif

        @forelse($datosConsolidados as $validador)
            <div class="validador-section">
                <div class="validador-header">{{ $validador->validador_nombre }} ({{ $validador->rol }})</div>
                <div class="validador-content">
                    <div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th class="text-right">Aprobados</th>
                                    <th class="text-right">Rechazados</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-validador-{{$validador->validador_id}}">
                                @foreach($datosGranulares->where('validador_id', $validador->validador_id) as $detalle)
                                    <tr class="doc-row" data-doc-name="{{ $detalle->documento_nombre }}">
                                        <td>{{ $detalle->documento_nombre }}</td>
                                        <td class="text-right">{{ $detalle->aprobados }}</td>
                                        <td class="text-right">{{ $detalle->rechazados }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="chart-container">
                        <canvas id="chart-{{ $validador->validador_id }}"></canvas>
                    </div>
                </div>
            </div>
        @empty
            <p style="text-align: center;">No hay datos de producción para mostrar.</p>
        @endforelse
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const datosConsolidados = @json($datosConsolidados);
            const todosLosDocumentos = @json($listaDeDocumentos);
            const filterGrid = document.getElementById('document-filter-grid');

            // 1. Crear filtros de documentos
            todosLosDocumentos.forEach(docName => {
                const label = document.createElement('label');
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = true;
                checkbox.value = docName;
                checkbox.addEventListener('change', updateTableVisibility);
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(docName));
                filterGrid.appendChild(label);
            });

            // 2. Función para actualizar visibilidad
            function updateTableVisibility() {
                const checkedDocs = Array.from(filterGrid.querySelectorAll('input:checked')).map(cb => cb.value);
                document.querySelectorAll('.doc-row').forEach(row => {
                    if (checkedDocs.includes(row.dataset.docName)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            // 3. Crear gráficos de torta
            datosConsolidados.forEach(validador => {
                const ctx = document.getElementById(`chart-${validador.validador_id}`);
                if (ctx) {
                    const total = validador.aprobados + validador.rechazados + validador.errores;
                    if (total > 0) {
                        new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels: [
                                    `Aprobados (${validador.aprobados})`,
                                    `Rechazados (${validador.rechazados})`,
                                    `Errores (*) (${validador.errores})`
                                ],
                                datasets: [{
                                    data: [validador.aprobados, validador.rechazados, validador.errores],
                                    backgroundColor: ['#2ecc71', '#f39c12', '#e74c3c'],
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { position: 'top' },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.label || '';
                                                let value = context.raw;
                                                let percentage = ((value / total) * 100).toFixed(1);
                                                return `${label}: ${percentage}%`;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    } else {
                        ctx.parentElement.innerHTML = '<p style="font-size:12px; color:#7f8c8d; text-align:center;">Sin actividad registrada.</p>';
                    }
                }
            });
        });
    </script>
</body>
</html>