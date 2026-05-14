<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Ejecutivo de Carga Documental (IMC)</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #374151; margin: 0; padding: 0; }
        .page-header { background: linear-gradient(to right, #4F46E5, #7C3AED); color: white; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .page-header h1 { margin: 0; font-size: 24px; font-weight: 900; letter-spacing: 1px; }
        .page-header p { margin: 5px 0 0 0; font-size: 12px; opacity: 0.9; }
        
        .info-bar { background-color: #F3F4F6; padding: 10px 20px; font-size: 9px; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
        .info-bar span { margin-right: 20px; }
        .info-bar strong { color: #111827; }

        .container { padding: 20px; }
        
        /* Dashboard Summary Cards */
        .dashboard { width: 100%; border-collapse: separate; border-spacing: 10px; }
        .card { padding: 15px; border-radius: 8px; color: white; text-align: left; }
        .card-title { font-size: 10px; text-transform: uppercase; font-weight: bold; opacity: 0.9; margin-bottom: 5px; }
        .card-value { font-size: 24px; font-weight: 900; margin: 0; }
        
        .bg-indigo { background: #4F46E5; }
        .bg-teal { background: #0D9488; }
        .bg-pink { background: #DB2777; }
        .bg-amber { background: #D97706; }

        .section-title { font-size: 16px; font-weight: bold; color: #111827; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid #E5E7EB; padding-bottom: 5px; }
        .sub-section-title { font-size: 14px; font-weight: bold; color: #4F46E5; margin-top: 15px; margin-bottom: 8px; }

        /* Modern Table */
        .modern-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1); }
        .modern-table th { background-color: #F9FAFB; color: #374151; font-weight: bold; font-size: 9px; text-transform: uppercase; padding: 10px; text-align: left; border-bottom: 1px solid #E5E7EB; }
        .modern-table td { padding: 8px 10px; font-size: 10px; border-bottom: 1px solid #E5E7EB; color: #4B5563; }
        .modern-table tbody tr:nth-child(even) { background-color: #F9FAFB; }
        
        .value-high { color: #DC2626; font-weight: bold; }
        .value-medium { color: #D97706; font-weight: bold; }
        .value-low { color: #059669; font-weight: bold; }

        .badge-active { background-color: #DEF7EC; color: #03543F; padding: 3px 8px; border-radius: 12px; font-size: 8px; font-weight: bold; }
        .badge-inactive { background-color: #FDE8E8; color: #9B1C1C; padding: 3px 8px; border-radius: 12px; font-size: 8px; font-weight: bold; }

        .footer { text-align: right; font-size: 8px; color: #9CA3AF; position: fixed; bottom: 10px; width: 100%; padding-right: 20px; }
        
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>REPORTE EJECUTIVO IMC</h1>
        <p>Índice Mensual de Carga Documental - OVAL Control</p>
    </div>
    <div class="info-bar">
        <span><strong>Fecha:</strong> {{ $fecha }}</span>
        <span><strong>Generado por:</strong> {{ $usuario }}</span>
    </div>

    <div class="container">
        <!-- Dashboard Header -->
        <table class="dashboard">
            <tr>
                <td class="card bg-indigo" style="width: 25%;">
                    <div class="card-title">PRINCIPALES INCLUIDAS</div>
                    <div class="card-value">{{ count($resumenPorMandante) }}</div>
                </td>
                <td class="card bg-teal" style="width: 25%;">
                    <div class="card-title">REGLAS ACTIVAS</div>
                    <div class="card-value">{{ $reglasActivas }} / {{ $totalReglas }}</div>
                </td>
                <td class="card bg-amber" style="width: 25%;">
                    <div class="card-title">IMC TOTAL (GLOBAL)</div>
                    <div class="card-value">{{ number_format($imcTotal, 4, ',', '.') }}</div>
                </td>
                <td class="card bg-pink" style="width: 25%;">
                    <div class="card-title">CARGAS DOCS / AÑO</div>
                    <div class="card-value">{{ number_format($cargasEstimadasAnio, 1, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        <!-- Resumen por Mandante y Entidad -->
        <div class="section-title">RESUMEN POR PRINCIPAL Y ENTIDAD</div>
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Principal</th>
                    <th style="width: 25%;">Entidad Controlada</th>
                    <th style="width: 15%; text-align: center;">Reglas Activas</th>
                    <th style="width: 15%; text-align: right;">IMC Total</th>
                    <th style="width: 15%; text-align: right;">Cargas Est. Año</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumenPorMandante as $ms)
                    @foreach($ms['entidades'] as $entidad)
                    <tr>
                        <td style="font-weight: bold; color: #111827;">{{ $ms['mandante']->razon_social }}</td>
                        <td>{{ $entidad['nombre'] }}</td>
                        <td style="text-align: center;">{{ $entidad['activas'] }} / {{ $entidad['total'] }}</td>
                        <td style="text-align: right; font-weight: bold; color: #0D9488;">{{ number_format($entidad['imc'], 4, ',', '.') }}</td>
                        <td style="text-align: right;">{{ number_format($entidad['cargas_anio'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #EEF2FF;">
                        <td colspan="2" style="font-weight: bold; color: #4F46E5; text-align: right; padding-right: 15px;">Total {{ $ms['mandante']->razon_social }}:</td>
                        <td style="text-align: center; font-weight: bold; color: #4F46E5;">{{ $ms['total_reglas'] }} reglas</td>
                        <td style="text-align: right; font-weight: bold; color: #4F46E5;">{{ number_format($ms['imc_total'], 4, ',', '.') }}</td>
                        <td style="text-align: right; font-weight: bold; color: #4F46E5;">{{ number_format($ms['imc_total'] * 12, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Top 10 Reglas con Mayor Carga -->
        @if($topReglas->count() > 0)
        <div class="section-title" style="margin-top: 30px;">TOP 10 REGLAS CON MAYOR CARGA DOCUMENTAL</div>
        <table class="modern-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 25%;">Principal</th>
                    <th style="width: 15%;">Entidad</th>
                    <th style="width: 35%;">Documento</th>
                    <th style="width: 10%; text-align: right;">IMC</th>
                    <th style="width: 10%; text-align: right;">Cargas/Año</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = 1; @endphp
                @foreach($topReglas as $regla)
                    @php
                        $imcClass = 'value-low';
                        if ($regla->imc >= 0.5) $imcClass = 'value-high';
                        elseif ($regla->imc >= 0.1) $imcClass = 'value-medium';
                    @endphp
                    <tr>
                        <td style="font-weight: bold; color: #374151;">{{ $rank++ }}</td>
                        <td>{{ $regla->mandante->razon_social ?? 'N/A' }}</td>
                        <td>{{ $regla->tipoEntidadControlada->nombre_entidad ?? 'N/A' }}</td>
                        <td style="font-weight: bold;">{{ $regla->nombreDocumento->nombre ?? 'N/A' }}</td>
                        <td style="text-align: right;" class="{{ $imcClass }}">{{ number_format($regla->imc ?? 0, 4, ',', '.') }}</td>
                        <td style="text-align: right; color: #4B5563;">{{ number_format(($regla->imc ?? 0) * 12, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        
        <div style="font-size: 8px; color: #9CA3AF; margin-top: 10px; font-style: italic;">
            * El IMC (Índice Mensual de Carga) representa el número estimado de veces que se cargará un documento cada mes. Un IMC alto puede requerir mayor supervisión.
        </div>
    </div>
    
    <div class="page-break"></div>

    <div class="page-header" style="background: linear-gradient(to right, #1F2937, #374151); padding: 15px 20px;">
        <h1 style="font-size: 20px;">DETALLE POR PRINCIPAL</h1>
        <p>Desglose de reglas documentales e indicadores</p>
    </div>

    <div class="container">
        @foreach($resumenPorMandante as $ms)
            <div class="sub-section-title" style="font-size: 16px; border-bottom: 1px solid #E5E7EB; padding-bottom: 5px; margin-top: 0; padding-top: 10px;">
                {{ $ms['mandante']->razon_social }}
                <span style="font-size: 10px; color: #6B7280; font-weight: normal; float: right; margin-top: 4px;">
                    IMC Subtotal: {{ number_format($ms['imc_total'], 4, ',', '.') }}
                </span>
            </div>
            
            @foreach($ms['entidades'] as $entidad)
            <div style="margin-left: 10px;">
                <div style="font-weight: bold; color: #4F46E5; margin-top: 15px; margin-bottom: 8px; font-size: 12px; background-color: #EEF2FF; padding: 5px 10px; border-radius: 4px;">
                    Entidad: {{ $entidad['nombre'] }} 
                    <span style="font-weight: normal; color: #6B7280; font-size: 9px; float: right; margin-top: 2px;">IMC Entidad: {{ number_format($entidad['imc'], 4, ',', '.') }}</span>
                </div>
                <table class="modern-table" style="margin-bottom: 5px;">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Documento</th>
                            <th style="width: 15%;">Tipo Vencimiento</th>
                            <th style="width: 15%; text-align: center;">Días Val. / Meses Est.</th>
                            <th style="width: 5%; text-align: center;">Est.</th>
                            <th style="width: 10%; text-align: right;">Cargas/Año</th>
                            <th style="width: 10%; text-align: right; color: #4F46E5;">IMC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entidad['reglas_detalle'] as $regla)
                        @php
                            $imcClass = 'value-low';
                            if ($regla->imc >= 0.5) $imcClass = 'value-high';
                            elseif ($regla->imc >= 0.1) $imcClass = 'value-medium';
                        @endphp
                        <tr>
                            <td style="font-weight: bold;">{{ $regla->nombreDocumento->nombre ?? 'N/A' }}</td>
                            <td>{{ $regla->tipoVencimiento->nombre ?? 'N/A' }}</td>
                            <td style="text-align: center; color: #6B7280;">
                                {{ $regla->dias_validez_documento ?? '-' }} / {{ $regla->imc_meses_estimados ?? 'Auto' }}
                            </td>
                            <td style="text-align: center;">
                                <span class="{{ $regla->is_active ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $regla->is_active ? 'SI' : 'NO' }}
                                </span>
                            </td>
                            <td style="text-align: right;">{{ number_format(($regla->imc ?? 0) * 12, 1, ',', '.') }}</td>
                            <td style="text-align: right;" class="{{ $imcClass }}">{{ number_format($regla->imc ?? 0, 4, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
            <div style="margin-bottom: 40px;"></div>
        @endforeach
    </div>

    <div class="footer">
        OVAL Control v8 - Reporte Ejecutivo
    </div>
</body>
</html>
