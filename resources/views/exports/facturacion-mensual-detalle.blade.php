<table>
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold; font-size: 16px;">Detalle de Trabajadores Facturables</th>
        </tr>
        <tr>
            <th colspan="4">Principal: {{ $datos['mandanteNombre'] }}</th>
        </tr>
        <tr>
            <th colspan="4">Período: {{ \Carbon\Carbon::parse($datos['fechaDesde'])->format('d-m-Y') }} al {{ \Carbon\Carbon::parse($datos['fechaHasta'])->format('d-m-Y') }}</th>
        </tr>
        <tr></tr>
    </thead>
    <tbody>
        {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
        @foreach($datos['resumen'] as $resumenItem)
            @php
                $detalleKey = $resumenItem->contratista_id . '-' . $resumenItem->mandante_id;
                $trabajadores = $datos['detalle']->get($detalleKey);
            @endphp

            @if($trabajadores)
                <tr>
                    <td colspan="4" style="font-weight: bold; background-color: #cccccc;">
                        Contratista: {{ $resumenItem->razon_social }} ({{ $resumenItem->rut_contratista }})
                        @if($datos['showMandanteColumn'])
                            | Principal: {{ $resumenItem->mandante_nombre }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold; border: 1px solid #000;">RUT Trabajador</td>
                    <td style="font-weight: bold; border: 1px solid #000;">Nombres</td>
                    <td style="font-weight: bold; border: 1px solid #000;">Apellido Paterno</td>
                    <td style="font-weight: bold; border: 1px solid #000;">Fecha Creación Ficha</td>
                </tr>
                @foreach($trabajadores as $trabajador)
                    <tr>
                        <td style="border: 1px solid #000;">{{ $trabajador->rut }}</td>
                        <td style="border: 1px solid #000;">{{ $trabajador->nombres }}</td>
                        <td style="border: 1px solid #000;">{{ $trabajador->apellido_paterno }}</td>
                        <td style="border: 1px solid #000;">{{ $trabajador->created_at->format('d-m-Y') }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="4"></td></tr>
            @endif
        @endforeach
        {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
    </tbody>
</table>