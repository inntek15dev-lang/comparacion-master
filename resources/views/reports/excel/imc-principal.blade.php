<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <td colspan="9" style="background-color: #1F2937; color: #ffffff; font-size: 14px; font-weight: bold; text-align: center; height: 30px; vertical-align: middle;">
                DETALLE DE CARGA DOCUMENTAL - {{ mb_strtoupper($mandante->razon_social) }}
            </td>
        </tr>
        <tr><td colspan="9"></td></tr>
        <tr>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 25px;">Entidad Controlada</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 45px;">Documento</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 15px;">Tipo Vencimiento</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: center;">Días Validez (Aut)</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: center;">Meses Est. (Man)</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: center;">Meses Vigencia Final</th>
            <th style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: right;">Cargas/Año</th>
            <th style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: right;">IMC (docs/mes)</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 10px; text-align: center;">Estado</th>
        </tr>
        @foreach($data as $row)
        <tr>
            <td style="border: 1px solid #E5E7EB; color: #111827; font-weight: bold;">{{ $row['Entidad'] }}</td>
            <td style="border: 1px solid #E5E7EB;">{{ $row['Documento'] }}</td>
            <td style="border: 1px solid #E5E7EB;">{{ $row['Tipo Vencimiento'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: center;">{{ $row['Días Validez (Aut)'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: center;">{{ $row['Meses Estimados (Man)'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: center;">{{ $row['Meses Vigencia Final'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: right; font-weight: bold; color: #4B5563;">{{ $row['Cargas/Año'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: right; font-weight: bold; color: #0D9488;">{{ $row['IMC (docs/mes)'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: center; color: {{ $row['Estado'] == 'Activa' ? '#059669' : '#DC2626' }}; font-weight: bold;">{{ $row['Estado'] }}</td>
        </tr>
        @endforeach
    </table>
</body>
</html>
