<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Reglas Documentales</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4F46E5; padding-bottom: 10px; }
        .header h1 { color: #4F46E5; margin: 0; font-size: 18px; }
        .info { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #4F46E5; color: white; padding: 8px; text-align: left; }
        td { border: 1px solid #E5E7EB; padding: 6px; }
        tr:nth-child(even) { background-color: #F9FAFB; }
        .footer { text-align: right; font-size: 8px; color: #6B7280; position: fixed; bottom: 0; width: 100%; }
        .tag { display: inline-block; padding: 2px 5px; border-radius: 4px; font-size: 8px; background: #E5E7EB; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Listado de Reglas Documentales</h1>
        <p>Sistema OVAL Control</p>
    </div>

    <div class="info">
        <p><strong>Fecha de Generación:</strong> {{ $fecha }}</p>
        <p><strong>Generado por:</strong> {{ $usuario }}</p>
    </div>

    @if(!($soloHistorial ?? false))
        @foreach($reglas as $regla)
        <div style="border: 1px solid #9CA3AF; margin-bottom: 20px; border-radius: 4px; page-break-inside: avoid;">
            <div style="background-color: #4F46E5; color: white; padding: 6px 10px; font-weight: bold; font-size: 11px;">
                Regla #{{ $regla->id }} - {{ $regla->nombreDocumento?->nombre ?? 'Sin Documento' }}
                <span style="float: right; background: {{ $regla->is_active ? '#10B981' : '#EF4444' }}; padding: 1px 6px; border-radius: 10px; font-size: 9px;">
                    {{ $regla->is_active ? 'ACTIVA' : 'INACTIVA' }}
                </span>
            </div>
            
            <table style="margin-bottom: 0; border: none;">
                <tr style="background: none;">
                    <td style="width: 50%; border: none; vertical-align: top; padding-right: 15px;">
                        <div style="margin-bottom: 8px;">
                            <span style="color: #6B7280; font-size: 9px;">Principal (Mandante)</span><br>
                            <strong>{{ $regla->mandante?->razon_social ?? 'N/A' }}</strong>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <span style="color: #6B7280; font-size: 9px;">Entidad Controlada</span><br>
                            <strong>{{ $regla->tipoEntidadControlada?->nombre_entidad ?? 'N/A' }}</strong>
                        </div>
                        <div style="margin-bottom: 8px;">
                            <span style="color: #6B7280; font-size: 9px;">Condición Empresa / Persona</span><br>
                            {{ $regla->aplicaEmpresaCondicion?->nombre ?? 'Ninguna' }} / {{ $regla->aplicaPersonaCondicion?->nombre ?? 'Ninguna' }}
                        </div>
                    </td>
                    <td style="width: 50%; border: none; vertical-align: top;">
                        <table style="width: 100%; border: none; margin: 0; font-size: 9px;">
                            <tr style="background: none;">
                                <td style="border: none; padding: 2px 0;"><strong>Valor Nominal:</strong> {{ $regla->valor_nominal_documento ?: 'N/A' }}</td>
                                <td style="border: none; padding: 2px 0;"><strong>Formato:</strong> {{ $regla->formatoDocumento?->nombre ?? 'N/A' }}</td>
                            </tr>
                            <tr style="background: none;">
                                <td style="border: none; padding: 2px 0;"><strong>Vencimiento:</strong> {{ $regla->tipoVencimiento?->nombre ?? 'N/A' }}</td>
                                <td style="border: none; padding: 2px 0;"><strong>Días Validez:</strong> {{ $regla->dias_validez_documento ?? '-' }}</td>
                            </tr>
                            <tr style="background: none;">
                                <td style="border: none; padding: 2px 0;"><strong>Aviso Vencimiento:</strong> {{ $regla->dias_aviso_vencimiento ?? '-' }} días</td>
                                <td style="border: none; padding: 2px 0;"><strong>Días Gracia Carga:</strong> {{ $regla->dias_gracia_carga ?? '-' }}</td>
                            </tr>
                        </table>

                        <div style="margin-top: 8px; font-size: 8px;">
                            <span class="tag" style="background: {{ $regla->valida_emision ? '#D1FAE5; color: #065F46' : '#FEE2E2; color: #991B1B' }}">Valida Emisión</span>
                            <span class="tag" style="background: {{ $regla->valida_vencimiento ? '#D1FAE5; color: #065F46' : '#FEE2E2; color: #991B1B' }}">Valida Vencimiento</span>
                            <span class="tag" style="background: {{ $regla->requiere_validacion_mandante ? '#D1FAE5; color: #065F46' : '#FEE2E2; color: #991B1B' }}">Validación Mandante</span>
                            <span class="tag" style="background: {{ $regla->mostrar_historico_documento ? '#DBEAFE; color: #1E40AF' : '#F3F4F6' }}">Histórico</span>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Borde separador interior -->
            <div style="border-top: 1px dotted #D1D5DB; margin: 5px 10px;"></div>

            <div style="padding: 5px 10px;">
                <div style="margin-bottom: 5px; color: #4F46E5;"><strong>Aplicabilidad:</strong></div>
                <div style="margin-bottom: 3px;"><strong>Cargos:</strong> {{ $regla->cargosAplica->pluck('nombre_cargo')->join(', ') ?: 'Ninguno' }}</div>
                <div style="margin-bottom: 3px;"><strong>Nacionalidades:</strong> {{ $regla->nacionalidadesAplica->pluck('nombre')->join(', ') ?: 'Ninguna' }}</div>
                <div style="margin-bottom: 3px;"><strong>Tipos Permanencia:</strong> {{ $regla->tiposPermanenciaAplica->pluck('nombre')->join(', ') }}</div>
                <div style="margin-bottom: 3px;"><strong>U. Organizacionales:</strong> {{ $regla->unidadesOrganizacionales->pluck('nombre_unidad')->join(', ') ?: 'Todas' }}</div>
                
                @if($regla->tiposVehiculoAplica->count() > 0 || $regla->tiposMaquinariaAplica->count() > 0 || $regla->tiposEmbarcacionAplica->count() > 0)
                <div style="margin-bottom: 3px;">
                    <strong>Equipos:</strong> 
                    @if($regla->tiposVehiculoAplica->count() > 0) Vehículos: <i>{{ $regla->tiposVehiculoAplica->pluck('nombre')->join(', ') }}</i>; @endif
                    @if($regla->tiposMaquinariaAplica->count() > 0) Maquinaria: <i>{{ $regla->tiposMaquinariaAplica->pluck('nombre')->join(', ') }}</i>; @endif
                    @if($regla->tiposEmbarcacionAplica->count() > 0) Embarcaciones: <i>{{ $regla->tiposEmbarcacionAplica->pluck('nombre')->join(', ') }}</i> @endif
                    @if($regla->tenenciasAplica->count() > 0) | Tenencias: <i>{{ $regla->tenenciasAplica->pluck('nombre')->join(', ') }}</i> @endif
                </div>
                @endif

                @if($regla->rut_especificos || $regla->rut_excluidos)
                <div style="margin-bottom: 3px; font-size: 8.5px;">
                    @if($regla->rut_especificos) <strong>RUTs Específicos:</strong> {{ $regla->rut_especificos }} <br> @endif
                    @if($regla->rut_excluidos) <strong style="color: #991B1B">RUTs Excluidos:</strong> {{ $regla->rut_excluidos }} @endif
                </div>
                @endif
            </div>

            <div style="background-color: #F9FAFB; padding: 10px; border-top: 1px solid #E5E7EB;">
                <div style="margin-bottom: 5px; color: #4F46E5;"><strong>Criterios de Evaluación:</strong></div>
                @if($regla->criterios->count() > 0)
                <ul style="margin: 0; padding-left: 15px; font-size: 9px;">
                    @foreach($regla->criterios as $c)
                    <li style="margin-bottom: 3px;">
                        <strong>{{ $c->criterioEvaluacion->nombre_criterio ?? 'N/A' }}</strong>
                        @if($c->subCriterio) > {{ $c->subCriterio->nombre }} @endif
                        @if($c->aclaracionCriterio) (<i>{{ $c->aclaracionCriterio->titulo }}</i>) @endif
                        @if($c->textoRechazo) <br><span style="color: #991b1b; display: inline-block; margin-left: 10px;">↳ Rechazo: "{{ $c->textoRechazo->titulo }}"</span> @endif
                        <span class="tag" style="float: right;">{{ strtoupper($c->fuente_validacion) }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p style="margin: 0; font-size: 9px; font-style: italic; color: #6B7280;">No hay criterios configurados para esta regla.</p>
                @endif
            </div>
        </div>
        @endforeach
    @endif

    @if($incluirHistorial && count($historial) > 0)
    <div style="page-break-before: always;"></div>
    <div class="header">
        <h1>Historial de Cambios</h1>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:10%">FECHA</th>
                <th style="width:12%">REGLA</th>
                <th style="width:12%">USUARIO</th>
                <th style="width:12%">EVENTO</th>
                <th style="width:10%">TIPO / CAMPO</th>
                <th style="width:14%; background-color:#B91C1C;">VALOR ANTERIOR (ROJO)</th>
                <th style="width:14%; background-color:#166534;">VALOR NUEVO (VERDE)</th>
                <th style="width:16%; background-color:#1E40AF;">CAMBIOS (AZUL)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($historial as $actividad)
            @php
                $props = $actividad->properties;
                $filas_cambios = [];

                if (isset($props['attributes'])) {
                    foreach ($props['attributes'] as $campo => $valores) {
                        if (is_array($valores) && isset($valores['old'], $valores['new'])) {
                            $oldVal = $valores['old'];
                            $newVal = $valores['new'];
                        } else {
                            $newVal = $valores;
                            $oldVal = $props['old'][$campo] ?? 'N/A';
                        }
                        
                        $diffVal = ($oldVal == $newVal) ? 'Sin cambios' : "$oldVal por $newVal";
                        
                        $filas_cambios[] = [
                            'tipo' => 'ATRIBUTO', 
                            'campo' => $campo, 
                            'old' => $oldVal, 
                            'new' => $newVal,
                            'diff' => $diffVal
                        ];
                    }
                }
                if (isset($props['relations'])) {
                    foreach ($props['relations'] as $relNombre => $relData) {
                        $oldRaw = $relData['old'] ?? 'NINGUNA';
                        $newRaw = $relData['new'] ?? 'NINGUNA';
                        
                        $oldArr = $oldRaw === 'NINGUNA' ? [] : array_map('trim', explode(',', $oldRaw));
                        $newArr = $newRaw === 'NINGUNA' ? [] : array_map('trim', explode(',', $newRaw));
                        
                        $agregados = array_diff($newArr, $oldArr);
                        $eliminados = array_diff($oldArr, $newArr);
                        
                        $diffStrs = [];
                        foreach ($agregados as $add) { if($add) $diffStrs[] = "(+) " . $add; }
                        foreach ($eliminados as $rm) { if($rm) $diffStrs[] = "(-) " . $rm; }
                        $diffFinal = empty($diffStrs) ? 'Sin cambios' : implode('<br>', $diffStrs);
                        
                        $filas_cambios[] = [
                            'tipo' => 'RELACIÓN', 
                            'campo' => $relNombre, 
                            'old' => $oldRaw, 
                            'new' => $newRaw,
                            'diff' => $diffFinal
                        ];
                    }
                }
                if (isset($props['criterios_originales']) || isset($props['criterios_nuevos'])) {
                    $origList = $props['criterios_originales'] ?? [];
                    $newList = $props['criterios_nuevos'] ?? [];
                    
                    $cToStrPdf = function($c) {
                        $p = [];
                        if(!empty($c['Criterio Evaluación'])) $p[] = "<b>".$c['Criterio Evaluación']."</b>";
                        if(!empty($c['Sub-criterio']) && $c['Sub-criterio']!=='Ninguno') $p[] = $c['Sub-criterio'];
                        if(!empty($c['Aclaración Criterio']) && $c['Aclaración Criterio']!=='Ninguna') $p[] = "<i>".$c['Aclaración Criterio']."</i>";
                        return implode(' > ', $p);
                    };

                    $oldStr = empty($origList) ? 'NINGUNO' : collect($origList)->map($cToStrPdf)->join("<br>");
                    $newStr = empty($newList) ? 'NINGUNO' : collect($newList)->map($cToStrPdf)->join("<br>");

                    $diffStrs = [];
                    $maxItems = max(count($origList), count($newList));
                    for ($i = 0; $i < $maxItems; $i++) {
                        $orig = $origList[$i] ?? null;
                        $new = $newList[$i] ?? null;
                        
                        if ($orig && !$new) {
                            $diffStrs[] = "<span style='color: #991B1B;'>(-) ".strip_tags($cToStrPdf($orig))."</span>";
                        } elseif (!$orig && $new) {
                            $diffStrs[] = "<span style='color: #166534;'>(+) ".strip_tags($cToStrPdf($new))."</span>";
                        } elseif ($orig && $new) {
                            $subDiffs = [];
                            foreach ($orig as $k => $v) {
                                if (($new[$k] ?? null) !== $v) {
                                    $subDiffs[] = "<b>$k</b>: <span style='color:#991B1B; text-decoration:line-through;'>$v</span> por <span style='color:#166534;'>{$new[$k]}</span>";
                                }
                            }
                            if (!empty($subDiffs)) {
                                $diffStrs[] = "<u>Criterio " . ($i + 1) . "</u>:<br>" . implode("<br>", $subDiffs);
                            }
                        }
                    }
                    $diffFinal = empty($diffStrs) ? 'Sin cambios' : implode("<br><br>", $diffStrs);

                    $filas_cambios[] = [
                        'tipo' => 'CRITERIOS', 
                        'campo' => 'Criterios de Eval.', 
                        'old' => $oldStr, 
                        'new' => $newStr,
                        'diff' => $diffFinal
                    ];
                } elseif (isset($props['criterios'])) {
                    $oldStr = $props['criterios']['old'] ?? '(NINGUNO)';
                    $newStr = $props['criterios']['new'] ?? '(NINGUNO)';
                    
                    $oldClean = str_replace('||', '|', $oldStr);
                    $newClean = str_replace('||', '|', $newStr);
                    
                    $oldParts = array_filter(array_map('trim', explode('|', $oldClean)));
                    $newParts = array_filter(array_map('trim', explode('|', $newClean)));
                    
                    $agregados = array_diff($newParts, $oldParts);
                    $eliminados = array_diff($oldParts, $newParts);
                    
                    $diffStrs = [];
                    foreach ($eliminados as $rm) {
                        if ($rm !== '(NINGUNO)') $diffStrs[] = "<span style='color: #991B1B;'>(-) $rm</span>";
                    }
                    foreach ($agregados as $add) {
                        if ($add !== '(NINGUNO)') $diffStrs[] = "<span style='color: #166534;'>(+) $add</span>";
                    }
                    $diffFinal = empty($diffStrs) ? 'Cambio menor / de orden' : implode("<br>", $diffStrs);
                    
                    $filas_cambios[] = [
                        'tipo' => 'CRITERIOS', 'campo' => 'Criterios (Legacy)', 
                        'old' => $oldStr, 
                        'new' => $newStr,
                        'diff' => $diffFinal
                    ];
                }
                if (empty($filas_cambios)) {
                    $filas_cambios[] = ['tipo' => '-', 'campo' => '-', 'old' => 'Creación inicial', 'new' => '-', 'diff' => '-'];
                }

                $numCambios   = count($filas_cambios);
                $subject_label = $actividad->subject_id;
            @endphp

            @foreach($filas_cambios as $i => $cambio)
            <tr style="{{ $loop->parent->odd ? 'background-color:#F9FAFB;' : '' }}">
                @if($i === 0)
                <td rowspan="{{ $numCambios }}" style="vertical-align:top; font-size:7px;">{{ $actividad->created_at->format('d/m/Y H:i') }}</td>
                <td rowspan="{{ $numCambios }}" style="vertical-align:top; font-size:7px;">Regla #{{ $subject_label }}</td>
                <td rowspan="{{ $numCambios }}" style="vertical-align:top; font-size:7px;">{{ $actividad->causer?->name ?? 'Sistema' }}</td>
                <td rowspan="{{ $numCambios }}" style="vertical-align:top; font-size:7px; font-weight:bold;">{{ strtoupper($actividad->description) }}</td>
                @endif
                <td style="font-size:7px; background-color:#F3F4F6; font-weight:bold; color:#374151;">{{ $cambio['tipo'] }}<br><span style="font-weight:normal; color:#6B7280;">{{ $cambio['campo'] }}</span></td>
                <td style="font-size:7px; background-color:#FEF2F2; color:#991B1B;">{!! $cambio['old'] !!}</td>
                <td style="font-size:7px; background-color:#F0FDF4; color:#166534; font-weight:bold;">{!! $cambio['new'] !!}</td>
                <td style="font-size:7px; background-color:#EFF6FF; color:#1E40AF; font-weight:bold;">{!! $cambio['diff'] !!}</td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Página generada el {{ $fecha }} - OVAL Control v8
    </div>
</body>
</html>
