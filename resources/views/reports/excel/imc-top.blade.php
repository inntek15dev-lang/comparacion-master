<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <tr>
            <td colspan="6" style="background-color: #DC2626; color: #ffffff; font-size: 16px; font-weight: bold; text-align: center; height: 30px; vertical-align: middle;">
                TOP 25 MAYOR CARGA DOCUMENTAL ANUAL
            </td>
        </tr>
        <tr><td colspan="6"></td></tr>
        <tr>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 10px; text-align: center;">Ranking</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 30px;">Principal</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 25px;">Entidad Controlada</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 45px;">Documento</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: right;">Cargas/Año</th>
            <th style="background-color: #FEE2E2; color: #991B1B; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: right;">IMC (docs/mes)</th>
        </tr>
        @forelse($data as $row)
        <tr>
            <td style="border: 1px solid #E5E7EB; text-align: center; font-weight: bold; color: #111827;">{{ $row['Ranking'] }}</td>
            <td style="border: 1px solid #E5E7EB;">{{ $row['Principal'] }}</td>
            <td style="border: 1px solid #E5E7EB; color: #4B5563;">{{ $row['Entidad'] }}</td>
            <td style="border: 1px solid #E5E7EB; font-weight: bold;">{{ $row['Documento'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: right; font-weight: bold; color: #DC2626;">{{ $row['Cargas/Año'] }}</td>
            <td style="border: 1px solid #E5E7EB; text-align: right; color: #B91C1C;">{{ $row['IMC (docs/mes)'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; border: 1px solid #E5E7EB; padding: 20px;">No hay datos suficientes para calcular ranking.</td>
        </tr>
        @endforelse
    </table>
</body>
</html>
