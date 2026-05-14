<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <table>
        <!-- Header -->
        <tr>
            <td colspan="6" style="background-color: #4F46E5; color: #ffffff; font-size: 16px; font-weight: bold; text-align: center; height: 30px; vertical-align: middle;">
                REPORTE EJECUTIVO DE CARGA DOCUMENTAL (IMC)
            </td>
        </tr>
        <tr>
            <td colspan="6" style="background-color: #E0E7FF; color: #3730A3; font-size: 11px; text-align: center; height: 20px; vertical-align: middle;">
                Sistema OVAL Control - Fecha de Generación: {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
        <tr><td colspan="6"></td></tr>

        <!-- Dashboard Cards -->
        <tr>
            <td colspan="2" style="background-color: #4F46E5; color: #ffffff; font-size: 10px; font-weight: bold; text-align: center;">PRINCIPALES INCLUIDAS</td>
            <td colspan="2" style="background-color: #0D9488; color: #ffffff; font-size: 10px; font-weight: bold; text-align: center;">REGLAS ACTIVAS</td>
            <td colspan="2" style="background-color: #D97706; color: #ffffff; font-size: 10px; font-weight: bold; text-align: center;">IMC TOTAL (GLOBAL)</td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #4F46E5; color: #ffffff; font-size: 20px; font-weight: bold; text-align: center; height: 40px; vertical-align: middle;">{{ $totalMandantes }}</td>
            <td colspan="2" style="background-color: #0D9488; color: #ffffff; font-size: 20px; font-weight: bold; text-align: center; height: 40px; vertical-align: middle;">{{ $reglasActivas }} / {{ $totalReglas }}</td>
            <td colspan="2" style="background-color: #D97706; color: #ffffff; font-size: 20px; font-weight: bold; text-align: center; height: 40px; vertical-align: middle;">{{ number_format($imcTotal, 4, ',', '.') }}</td>
        </tr>
        <tr><td colspan="6"></td></tr>

        <!-- Detalle -->
        <tr>
            <td colspan="6" style="background-color: #1F2937; color: #ffffff; font-size: 12px; font-weight: bold; text-align: left; height: 25px; vertical-align: middle;">
                RESUMEN POR ENTIDAD Y PRINCIPAL
            </td>
        </tr>
        <tr>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 30px;">Principal</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 25px;">Entidad Controlada</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: center;">Total Reglas</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 15px; text-align: center;">Reglas Activas</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 20px; text-align: center;">IMC Total</th>
            <th style="background-color: #F3F4F6; color: #374151; font-weight: bold; border: 1px solid #D1D5DB; width: 20px; text-align: center;">Cargas Est./Año</th>
        </tr>

        @forelse($data as $row)
            @if($row['is_total'] ?? false)
            <tr>
                <td style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; border: 1px solid #D1D5DB;">{{ $row['Principal'] }}</td>
                <td style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; border: 1px solid #D1D5DB;">{{ $row['Entidad'] }}</td>
                <td style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; text-align: center; border: 1px solid #D1D5DB;">{{ $row['Total Reglas'] }}</td>
                <td style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; text-align: center; border: 1px solid #D1D5DB;">{{ $row['Reglas Activas'] }}</td>
                <td style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; text-align: right; border: 1px solid #D1D5DB;">{{ number_format($row['IMC Total'], 4, ',', '.') }}</td>
                <td style="background-color: #EEF2FF; color: #4F46E5; font-weight: bold; text-align: right; border: 1px solid #D1D5DB;">{{ number_format($row['Cargas Est'], 2, ',', '.') }}</td>
            </tr>
            <tr><td colspan="6"></td></tr>
            @else
            <tr>
                <td style="color: #111827; font-weight: bold; border: 1px solid #E5E7EB;">{{ $row['Principal'] }}</td>
                <td style="border: 1px solid #E5E7EB;">{{ $row['Entidad'] }}</td>
                <td style="text-align: center; border: 1px solid #E5E7EB;">{{ $row['Total Reglas'] }}</td>
                <td style="text-align: center; border: 1px solid #E5E7EB;">{{ $row['Reglas Activas'] }}</td>
                <td style="text-align: right; color: #0D9488; font-weight: bold; border: 1px solid #E5E7EB;">{{ number_format($row['IMC Total'], 4, ',', '.') }}</td>
                <td style="text-align: right; border: 1px solid #E5E7EB;">{{ number_format($row['Cargas Est'], 2, ',', '.') }}</td>
            </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No se encontraron reglas para los Principales seleccionados.</td>
            </tr>
        @endforelse
    </table>
</body>
</html>
