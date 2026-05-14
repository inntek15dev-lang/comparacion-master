<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Facturación</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 4px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 0; font-size: 12px; }
        .total-row td { font-weight: bold; background-color: #f2f2f2; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Resumen de Facturación</h1>
        <p>Principal: {{ $datos['mandanteNombre'] }}</p>
        <p>Período: {{ \Carbon\Carbon::parse($datos['fechaDesde'])->format('d-m-Y') }} al {{ \Carbon\Carbon::parse($datos['fechaHasta'])->format('d-m-Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
                @if($datos['showMandanteColumn'])
                    <th>Principal</th>
                @endif
                {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
                <th>Razón Social Contratista</th>
                <th>RUT Contratista</th>
                <th style="text-align: center;">N° Trabajadores Facturables</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['resumen'] as $resumen)
                <tr>
                    {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
                    @if($datos['showMandanteColumn'])
                        <td>{{ $resumen->mandante_nombre }}</td>
                    @endif
                    {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
                    <td>{{ $resumen->razon_social }}</td>
                    <td>{{ $resumen->rut_contratista }}</td>
                    <td style="text-align: center;">{{ $resumen->trabajadores_facturables }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
                <td colspan="{{ $datos['showMandanteColumn'] ? '3' : '2' }}" style="text-align: right;">TOTAL GENERAL</td>
                {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
                <td style="text-align: center;">{{ $datos['totalGeneral'] }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="page-break"></div>

    <div class="header">
        <h1>Detalle de Trabajadores Facturables</h1>
    </div>

    {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
    @foreach($datos['resumen'] as $resumenItem)
        @php
            $detalleKey = $resumenItem->contratista_id . '-' . $resumenItem->mandante_id;
            $trabajadores = $datos['detalle']->get($detalleKey);
        @endphp
        
        @if($trabajadores)
            <h3 style="font-size: 14px; margin-top: 20px; background-color: #e9e9e9; padding: 5px;">
                Contratista: {{ $resumenItem->razon_social }} ({{ $resumenItem->rut_contratista }})
                @if($datos['showMandanteColumn'])
                    | Principal: {{ $resumenItem->mandante_nombre }}
                @endif
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>RUT Trabajador</th>
                        <th>Nombre Completo</th>
                        <th>Fecha Creación Ficha</th>
                        <th>Fecha Baja</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($trabajadores as $trabajador)
                        <tr>
                            <td>{{ $trabajador->rut }}</td>
                            <td>{{ $trabajador->nombre_completo }}</td>
                            <td>{{ $trabajador->created_at->format('d-m-Y') }}</td>
                            <td>{{ $trabajador->fecha_baja ? \Carbon\Carbon::parse($trabajador->fecha_baja)->format('d-m-Y') : 'Activo' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
    {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}

</body>
</html>