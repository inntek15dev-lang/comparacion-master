<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen de Facturación</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 4px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 0; font-size: 12px; }
        .total-row td { font-weight: bold; background-color: #f2f2f2; }
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
                @if($datos['showMandanteColumn'])
                    <th>Principal</th>
                @endif
                <th>Razón Social Contratista</th>
                <th>RUT Contratista</th>
                <th style="text-align: center;">N° Trabajadores Facturables</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos['resumen'] as $resumen)
                <tr>
                    @if($datos['showMandanteColumn'])
                        <td>{{ $resumen->mandante_nombre }}</td>
                    @endif
                    <td>{{ $resumen->razon_social }}</td>
                    <td>{{ $resumen->rut_contratista }}</td>
                    <td style="text-align: center;">{{ $resumen->trabajadores_facturables }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="{{ $datos['showMandanteColumn'] ? '3' : '2' }}" style="text-align: right;">TOTAL GENERAL</td>
                <td style="text-align: center;">{{ $datos['totalGeneral'] }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>