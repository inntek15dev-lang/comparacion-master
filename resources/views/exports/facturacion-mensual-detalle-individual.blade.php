<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detalle de Facturación</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333333; text-align: left; padding: 5px; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .header-info { margin-bottom: 20px; font-size: 12px; }
        .header-info strong { display: inline-block; width: 100px; }
    </style>
</head>
<body>
    <div class="header-info">
        <h2 style="font-size: 16px; margin-bottom: 10px;">Detalle de Trabajadores Facturables</h2>
        <p><strong>Principal:</strong> {{ $datos['mandanteNombre'] }}</p>
        <p><strong>Contratista:</strong> {{ $datos['contratista']->razon_social }} ({{ $datos['contratista']->rut_contratista }})</p>
        <p><strong>Período:</strong> {{ \Carbon\Carbon::parse($datos['fechaDesde'])->format('d-m-Y') }} al {{ \Carbon\Carbon::parse($datos['fechaHasta'])->format('d-m-Y') }}</p>
    </div>

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
            @foreach($datos['trabajadores'] as $trabajador)
                <tr>
                    <td>{{ $trabajador->rut }}</td>
                    <td>{{ $trabajador->nombre_completo }}</td>
                    <td>{{ $trabajador->created_at->format('d-m-Y') }}</td>
                    <td>{{ $trabajador->fecha_baja ? \Carbon\Carbon::parse($trabajador->fecha_baja)->format('d-m-Y') : 'Activo' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>