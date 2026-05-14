<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Supervisión Global</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dee2e6; padding: 6px; text-align: left; }
        .table thead th { background-color: #f2f2f2; font-weight: bold; }
        /* ================== INICIO DE LA MODIFICACIÓN ================== */
        .table tfoot td { background-color: #f8f9fa; font-weight: bold; }
        /* ================== FIN DE LA MODIFICACIÓN ==================== */
        .text-center { text-align: center; }
        .text-green { color: green; }
        .text-orange { color: orange; }
        h1 { text-align: center; }
    </style>
</head>
<body>
    <h1>Reporte de Supervisión Global de Cumplimiento</h1>
    <p><strong>Fecha de Generación:</strong> {{ now()->format('d-m-Y H:i:s') }}</p>
    
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                @if($incluirMandante)
                    <th>Principal</th>
                @endif
                <th>Contratista</th>
                <th>Lugar de Trabajo/Departamento</th>
                <th>U.O.</th>
                <th class="text-center">Empresa (%)</th>
                <th class="text-center">Trabajadores (% / Total)</th>
                <th class="text-center">Vehículos (% / Total)</th>
                <th class="text-center">Maquinaria (% / Total)</th>
                <th class="text-center">Embarcaciones (% / Total)</th>
            </tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach ($data as $item)
                <tr>
                    <td>{{ $i++ }}</td>
                    @if($incluirMandante)
                        <td>{{ $item['mandante_nombre'] }}</td>
                    @endif
                    <td>
                        {{ $item['razon_social'] }}<br>
                        <small>{{ $item['rut'] }}</small>
                    </td>
                    <td>{{ $item['lugar_trabajo_nombre_jerarquico'] }}</td>
                    <td>{{ $item['uo_nombre_jerarquico'] }}</td>
                    <td class="text-center">
                        @if(isset($item['cumplimiento_empresa']))
                            <span class="{{ $item['cumplimiento_empresa'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['cumplimiento_empresa'] }}%</span>
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($item['promedio_trabajadores']) && $item['promedio_trabajadores']['total'] > 0)
                            <span class="{{ $item['promedio_trabajadores']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_trabajadores']['promedio'] }}%</span> ({{ $item['promedio_trabajadores']['total'] }})
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($item['promedio_vehiculos']) && $item['promedio_vehiculos']['total'] > 0)
                            <span class="{{ $item['promedio_vehiculos']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_vehiculos']['promedio'] }}%</span> ({{ $item['promedio_vehiculos']['total'] }})
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($item['promedio_maquinarias']) && $item['promedio_maquinarias']['total'] > 0)
                            <span class="{{ $item['promedio_maquinarias']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_maquinarias']['promedio'] }}%</span> ({{ $item['promedio_maquinarias']['total'] }})
                        @else
                            N/A
                        @endif
                    </td>
                    <td class="text-center">
                        @if(isset($item['promedio_embarcaciones']) && $item['promedio_embarcaciones']['total'] > 0)
                            <span class="{{ $item['promedio_embarcaciones']['promedio'] < 100 ? 'text-orange' : 'text-green' }}">{{ $item['promedio_embarcaciones']['promedio'] }}%</span> ({{ $item['promedio_embarcaciones']['total'] }})
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        {{-- ================== INICIO DE LA MODIFICACIÓN ================== --}}
        @if(!empty($totales))
        <tfoot>
            <tr>
                <td colspan="{{ $incluirMandante ? 5 : 4 }}">TOTALES RECURSOS ÚNICOS:</td>
                <td class="text-center">{{ $totales['contratistas'] }} Contratistas</td>
                <td class="text-center">{{ $totales['trabajadores'] }} Trabajadores</td>
                <td class="text-center">{{ $totales['vehiculos'] }} Vehículos</td>
                <td class="text-center">{{ $totales['maquinarias'] }} Maquinarias</td>
                <td class="text-center">{{ $totales['embarcaciones'] }} Embarcaciones</td>
            </tr>
        </tfoot>
        @endif
        {{-- ================== FIN DE LA MODIFICACIÓN ==================== --}}
    </table>
</body>
</html>