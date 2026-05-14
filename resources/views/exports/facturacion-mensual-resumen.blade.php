<table>
    <thead>
        <tr>
            <th colspan="{{ $datos['showMandanteColumn'] ? '4' : '3' }}" style="font-weight: bold; font-size: 16px;">Resumen de Facturación</th>
        </tr>
        <tr>
            <th colspan="{{ $datos['showMandanteColumn'] ? '4' : '3' }}">Principal: {{ $datos['mandanteNombre'] }}</th>
        </tr>
        <tr>
            <th colspan="{{ $datos['showMandanteColumn'] ? '4' : '3' }}">Período: {{ \Carbon\Carbon::parse($datos['fechaDesde'])->format('d-m-Y') }} al {{ \Carbon\Carbon::parse($datos['fechaHasta'])->format('d-m-Y') }}</th>
        </tr>
        <tr></tr>
        <tr>
            {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
            @if($datos['showMandanteColumn'])
                <th style="font-weight: bold; border: 1px solid #000;">Principal</th>
            @endif
            {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
            <th style="font-weight: bold; border: 1px solid #000;">Razón Social Contratista</th>
            <th style="font-weight: bold; border: 1px solid #000;">RUT Contratista</th>
            <th style="font-weight: bold; border: 1px solid #000;">N° Trabajadores Facturables</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos['resumen'] as $resumen)
            <tr>
                {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
                @if($datos['showMandanteColumn'])
                    <td style="border: 1px solid #000;">{{ $resumen->mandante_nombre }}</td>
                @endif
                {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
                <td style="border: 1px solid #000;">{{ $resumen->razon_social }}</td>
                <td style="border: 1px solid #000;">{{ $resumen->rut_contratista }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $resumen->trabajadores_facturables }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            {{-- ================== INICIO DE LA MODIFICACIÓN CANÓNICA ================== --}}
            <td colspan="{{ $datos['showMandanteColumn'] ? '3' : '2' }}" style="font-weight: bold; border: 1px solid #000; text-align: right;">TOTAL GENERAL</td>
            {{-- ================== FIN DE LA MODIFICACIÓN CANÓNICA ================== --}}
            <td style="font-weight: bold; border: 1px solid #000; text-align: center;">{{ $datos['totalGeneral'] }}</td>
        </tr>
    </tfoot>
</table>