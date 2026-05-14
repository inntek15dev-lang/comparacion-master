<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Cumplimiento Laboral</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #000; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        th, td { border: 1px solid #CCC; padding: 2px 4px; text-align: center; vertical-align: middle; }
        
        .header-blue {
            background-color: #003a6c; 
            color: #ffffff; 
            font-weight: bold; 
            text-align: center; 
            padding: 6px; 
            font-size: 11px;
            border: 1px solid #003a6c;
        }
        .header-blue-small {
            background-color: #336699; 
            color: #ffffff; 
            font-weight: bold; 
            text-align: left; 
            padding: 3px 5px; 
            font-size: 9px;
            border: 1px solid #336699;
        }
        
        .bg-gray-headers {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .bg-gray-headers td { border: 1px solid #fff; border-bottom: 1px solid #ccc; }
        .cell-data {
            font-size: 9px;
            font-weight: bold;
            background-color: #fff;
        }
        .cell-data-normal {
            font-size: 9px;
            background-color: #fff;
        }
        
        .no-border { border: none !important; }
        
        p.intro-text {
            font-size: 9px;
            text-align: justify;
            margin-top: 10px;
            margin-bottom: 5px;
        }
        
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        
        .mt-1 { margin-top: 2px; }
        .mb-2 { margin-bottom: 6px; }
        
        /* Modificadores para detalles donde se imprime la nómina adentro */
        table.detalle-table { border: none; }
        table.detalle-table td { border: none; }
        
    </style>
</head>
<body>

    <!-- TOP HEADER LAYER -->
    <table style="border: none !important; margin-bottom: 0;">
        <tr>
            <td width="20%" class="no-border" style="text-align: left; padding: 0;">
                <img src="{{ base_path('logo_oval.png') }}" width="130" alt="OVAL">
            </td>
            <td width="65%" class="no-border" style="padding: 0 5px;">
                <div class="header-blue" style="border-radius: 2px;">
                    CERTIFICADO DE CUMPLIMIENTO LABORAL<br>
                    PERIODO {{ strtoupper($carpeta->nombre_mes) }} {{ $carpeta->anio }}
                </div>
            </td>
            <td width="15%" class="no-border" style="padding: 0;">
                <div class="header-blue" style="border-radius: 2px;">
                    FOLIO<br>
                    {{ $carpeta->vinculacion->id_registro ?? $carpeta->id }}-{{ $carpeta->anio }}-{{ str_pad($carpeta->mes, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($carpeta->id, 5, '0', STR_PAD_LEFT) }}
                </div>
            </td>
        </tr>
    </table>

    <p class="intro-text text-bold">
        Oval Ltda., ubicada en Av. Prat 199 of. 401 torre B, Concepción, certifica, respecto de la empresa solicitante que se individualiza a continuación, en su calidad de Contratista y de conformidad con la Solicitud de Certificado, Declaración Jurada y documentación cargada al Sistema OVAL con fecha {{ $carpeta->fecha_envio ? $carpeta->fecha_envio->format('d-m-Y') : '___-___-____' }}, por ésta, que se tuvo a la vista, lo siguiente:
    </p>

    <!-- SECTION 1 -->
    <div class="header-blue-small">
        <span style="float: right; font-size: 8px; font-weight: normal; margin-top: 1px;">
            <table style="margin: 0; padding: 0; width: auto; border: none;" class="no-border">
                <tr class="no-border"><td class="no-border" style="color:white; padding: 0;">Contratista {{ $carpeta->vinculacion->tipo_solicitud == 'SUBCONTRATISTA' ? '' : 'X' }}</td></tr>
                <tr class="no-border"><td class="no-border" style="color:white; padding: 0;">Subcontratista {{ $carpeta->vinculacion->tipo_solicitud == 'SUBCONTRATISTA' ? 'X' : '' }}</td></tr>
            </table>
        </span>
        1.- INDIVIDUALIZACION DE LA EMPRESA SOLICITANTE
    </div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="15%">RUT</td>
            <td width="85%" colspan="3">RAZON SOCIAL / NOMBRE / CONTRATO</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->vinculacion->contratista->rut ?? '-' }}</td>
            <td class="cell-data" colspan="3">{{ $carpeta->vinculacion->contratista->razon_social ?? '-' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td colspan="4">DOMICILIO</td>
        </tr>
        <tr>
            <td class="cell-data" colspan="4">{{ $carpeta->vinculacion->contratista->direccion ?? 'DOMICILIO DESCONOCIDO' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td width="15%">PAIS</td>
            <td width="60%" colspan="2">REGION</td>
            <td width="25%">COMUNA</td>
        </tr>
        <tr>
            <td class="cell-data">CHILE</td>
            <td class="cell-data" colspan="2">{{ $carpeta->vinculacion->contratista->comuna->region->nombre ?? '-' }}</td>
            <td class="cell-data">{{ $carpeta->vinculacion->contratista->comuna->nombre ?? '-' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td width="15%">FAX</td>
            <td width="60%" colspan="2">E-MAIL</td>
            <td width="25%">SITIO WEB</td>
        </tr>
        <tr>
            <td class="cell-data">0</td>
            <td class="cell-data" colspan="2">{{ $carpeta->vinculacion->contratista->email ?? '-' }}</td>
            <td class="cell-data"></td>
        </tr>
        <tr class="bg-gray-headers">
            <td colspan="4">CODIGO ACTIVIDAD ECONOMICA</td>
        </tr>
        <tr>
            <td class="cell-data" colspan="4"></td>
        </tr>
    </table>

    <!-- SECTION 2 -->
    <div class="header-blue-small mt-1">
        2.- ANTECEDENTES DE LA OBRA, EMPRESA O FAENA OBJETO DEL CERTIFICADO
    </div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td colspan="3">NOMBRE DE LA OBRA, FAENA, PUESTO DE TRABAJO O SERVICIO SEGÚN CONTRATO CIVIL.</td>
        </tr>
        <tr>
            <td class="cell-data" colspan="3">{{ $carpeta->vinculacion->dependencia->nombre ?? '-' }} | Contrato: {{ $carpeta->vinculacion->numero_contrato ?? 'N/A' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td colspan="3">DOMICILIO DE LA OBRA</td>
        </tr>
        <tr>
            <td class="cell-data" colspan="3">{{ $carpeta->vinculacion->dependencia->direccion ?? '-' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td width="33%">REGION</td>
            <td width="34%">COMUNA</td>
            <td width="33%">LOCALIDAD</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->vinculacion->dependencia->comuna->region->nombre ?? '-' }}</td>
            <td class="cell-data">{{ $carpeta->vinculacion->dependencia->comuna->nombre ?? '-' }}</td>
            <td class="cell-data"></td>
        </tr>
    </table>

    <!-- SECTION 2.1 -->
    <div class="header-blue-small mt-1">
        2.1.- SITUACION DE LOS TRABAJADORES DECLARADOS A LA FECHA DE LA SOLICITUD
    </div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="33%">CONTRATADOS EN EL PERIODO</td>
            <td width="34%">DESVINCULADOS EN EL PERIODO</td>
            <td width="33%">TOTAL DE TRABAJADORES SOLICITADOS</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->fin_contratados_periodo ?? 0 }}</td>
            <td class="cell-data">{{ $carpeta->fin_desvinculados_periodo ?? 0 }}</td>
            <td class="cell-data">{{ $carpeta->fin_total_vigentes ?? 0 }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td colspan="2">PERIODO REVISADO</td>
            <td width="33%">N° TRABAJADORES VERIFICADOS</td>
        </tr>
        <tr>
            <td class="cell-data text-uppercase" colspan="2">{{ strtoupper($carpeta->nombre_mes) }} {{ $carpeta->anio }}</td>
            <td class="cell-data">{{ $carpeta->fin_trabajadores_revisados ?? 0 }}</td>
        </tr>
    </table>

    <!-- Funciones Auxiliares -->
    @php
        $todasContingencias = $contingenciasAgrupadas;

        $contingenciasPorClasif = [];
        foreach ($todasContingencias as $clave => $grupo) {
            $contingenciasPorClasif[$grupo['clasificacion']][] = $grupo;
        }

        /**
         * Renderiza el detalle de contingencias retenibles en el PDF.
         * Cada grupo agrupa trabajadores con la misma falla (texto+clasificación).
         * - Cabecera: rango de códigos del grupo (ej. 100.001 - 100.003)
         * - Cada afectado: código individual [100.XXX] + RUT + nombre + monto
         */
        function renderDetalleContingencia($grupos, $t_plural_vacio = "NO REGISTRA") {
            if (!$grupos || count($grupos) === 0) {
                echo "<tr><td class='cell-data text-center' colspan='3'>" . $t_plural_vacio . "</td></tr>";
                return;
            }

            foreach ($grupos as $grupo) {
                $subtotal = collect($grupo['afectados'])->sum('monto');

                // Rango de códigos del grupo para la cabecera (trazabilidad normativa)
                $codigosAfectados = array_column($grupo['afectados'], 'codigo');
                $codigosAfectados = array_filter($codigosAfectados); // quitar nulos
                if (!empty($codigosAfectados)) {
                    $codigoMin = min($codigosAfectados);
                    $codigoMax = max($codigosAfectados);
                    $codigoCert = count($codigosAfectados) > 1
                        ? number_format($codigoMin, 0, ',', '.') . ' AL ' . number_format($codigoMax, 0, ',', '.')
                        : number_format($codigoMin, 0, ',', '.');
                } else {
                    $codigoCert = $grupo['codigo'] ?? 'S/C';
                }

                echo "<tr class='bg-gray-headers'>
                        <td width='33%' class='text-left'>Valor contingencia: $ " . number_format($subtotal, 0, ',', '.') . "</td>
                        <td width='34%' class='text-center'>Cant. de Trab.: " . count($grupo['afectados']) . "</td>
                        <td width='33%' class='text-right'>C&oacute;digo(s): {$codigoCert}</td>
                      </tr>";

                echo "<tr><td colspan='3' class='cell-data-normal text-left'>";

                $textoEncabezado = count($grupo['afectados']) > 1 ? $grupo['texto_plural'] : $grupo['texto_singular'];
                echo "<b>{$textoEncabezado}</b><br>";

                foreach ($grupo['afectados'] as $afectado) {
                    $codTrab = isset($afectado['codigo']) ? number_format($afectado['codigo'], 0, ',', '.') : '-';

                    // Línea del trabajador (contingencia original)
                    echo "<span style='color:#000;'>";
                    echo "<span style='font-family:monospace;font-size:8px;font-weight:bold;'>[" . $codTrab . "]</span>&nbsp; ";
                    echo $afectado['trabajador']->rut . " " . $afectado['trabajador']->nombre_completo . " &nbsp;&nbsp;&nbsp; $ " . number_format($afectado['monto'], 0, ',', '.');
                    echo "</span><br>";

                    // ── LÍNEA VERDE INDIVIDUAL: solo si este trabajador tiene solución ──
                    $montoSol = $afectado['monto_solucionado'] ?? 0;
                    if ($montoSol > 0) {
                        $saldoTrab  = max(0, ($afectado['monto'] ?? 0) - $montoSol);
                        $estadoTrab = ($montoSol >= ($afectado['monto'] ?? 0)) ? 'Solución total' : 'Solución parcial';
                        $fechaTrab  = ($afectado['fecha_solucion'] ?? null)
                            ? \Carbon\Carbon::parse($afectado['fecha_solucion'])->format('d/m/Y')
                            : '-';

                        echo "<div style='background-color:#008c4a;color:white;margin:2px 0 2px 10px;padding:2px 4px;border-radius:1px;'>";
                        echo "<table style='width:100%;border:none;margin:0;padding:0;'><tr>";
                        echo "<td style='border:none;color:white;font-weight:bold;font-size:8px;text-align:left;padding:0;width:38%;'>Solucionado: $ " . number_format($montoSol, 0, ',', '.') . "</td>";
                        echo "<td style='border:none;color:white;font-weight:bold;font-size:8px;text-align:left;padding:0;width:32%;'>Fecha: {$fechaTrab}</td>";
                        echo "<td style='border:none;color:white;font-weight:bold;font-size:8px;text-align:right;padding:0;width:30%;'>Estado: {$estadoTrab}</td>";
                        echo "</tr></table>";
                        echo "</div>";

                        // Saldo pendiente de ESTE trabajador
                        if ($saldoTrab > 0) {
                            echo "<div style='margin:1px 0 2px 10px;border-left:3px solid #d9534f;padding-left:5px;'>";
                            echo "<span style='color:#d9534f;font-size:8px;font-weight:bold;'>";
                            echo "[{$codTrab}] " . $afectado['trabajador']->rut . " " . $afectado['trabajador']->nombre_completo;
                            echo " , Pendiente: $ " . number_format($saldoTrab, 0, ',', '.');
                            echo "</span>";
                            echo "</div>";
                        }

                        // Observaciones individuales del complementario
                        if (!empty($afectado['observaciones_solucion'])) {
                            echo "<div style='font-size:8px;font-style:italic;margin:1px 0 3px 10px;color:#008c4a;font-weight:bold;border-left:3px solid #008c4a;padding-left:5px;line-height:1.2;'>";
                            echo $afectado['observaciones_solucion'];
                            echo "</div>";
                        }
                    }
                }

                echo "</td></tr>";
            }
        }
    @endphp

    <!-- SECTION 2.2 -->
    <div class="header-blue-small mt-1">
        2.2.- DETALLE DE REMUNERACIONES
    </div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="50%">PAGADAS</td>
            <td width="50%">NO PAGADAS</td>
        </tr>
        <tr>
            <td class="cell-data">$ {{ number_format($carpeta->fin_remuneraciones_pagadas ?? 0, 0, ',', '.') }}</td>
            <td class="cell-data">$ {{ number_format(collect($contingenciasPorClasif['Remuneraciones'] ?? [])->reduce(function($carry, $g){ return $carry + collect($g['afectados'])->sum('monto'); }, 0), 0, ',', '.') }}</td>
        </tr>
        <tr class="bg-gray-headers" style="background-color: #336699; color: white;">
            <td colspan="2" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #336699;">2.2.1.- DETALLE DE REMUNERACIONES NO PAGADAS</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Remuneraciones'] ?? []); @endphp
    </table>

    <!-- SECTION 2.3 -->
    <div class="header-blue-small mt-1">
        2.3.- ESTADO DE LAS COTIZACIONES PREVISIONALES
    </div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="50%">PAGADAS</td>
            <td width="50%">NO PAGADAS</td>
        </tr>
        <tr>
            <td class="cell-data">$ {{ number_format($carpeta->fin_cotizaciones_pagadas ?? 0, 0, ',', '.') }}</td>
            <td class="cell-data">$ {{ number_format(collect($contingenciasPorClasif['Cotizaciones'] ?? [])->reduce(function($carry, $g){ return $carry + collect($g['afectados'])->sum('monto'); }, 0), 0, ',', '.') }}</td>
        </tr>
        <tr class="bg-gray-headers" style="background-color: #336699; color: white;">
            <td colspan="2" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #336699;">2.3.1.- DETALLE DE COTIZACIONES NO PAGADAS</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Cotizaciones'] ?? []); @endphp
    </table>

    <!-- SECTION 2.4 -->
    <div class="header-blue-small mt-1">2.4.- DETALLE INDEMNIZACIONES</div>

    <!-- 2.4.1 -->
    <div class="header-blue-small mt-1" style="background-color: #4682B4; border-color: #4682B4;">2.4.1.- INDEMNIZACIONES SUSTITUTIVA DEL AVISO PREVIO</div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="33%">N° TRABS CON PAGO</td>
            <td width="34%">MONTO PAGADO</td>
            <td width="33%">NO PAGADAS</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->fin_aviso_previo_trabajadores ?? 0 }}</td>
            <td class="cell-data">$ {{ number_format($carpeta->fin_aviso_previo_total ?? 0, 0, ',', '.') }}</td>
            <td class="cell-data"></td>
        </tr>
        <tr class="bg-gray-headers" style="background-color: #4682B4; color: white;">
            <td colspan="3" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #4682B4;">2.4.1.2- DETALLE INDEMNIZACIONES NO PAGADAS AVISO PREVIO</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Aviso Previo'] ?? []); @endphp
    </table>

    <!-- 2.4.2 -->
    <div class="header-blue-small mt-1" style="background-color: #4682B4; border-color: #4682B4;">2.4.2.- INDEMNIZACIONES POR AÑOS DE SERVICIO</div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="33%">N° TRABS CON PAGO</td>
            <td width="34%">MONTO PAGADO</td>
            <td width="33%">NO PAGADAS</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->fin_anio_servicio_trabajadores ?? 0 }}</td>
            <td class="cell-data">$ {{ number_format($carpeta->fin_anio_servicio_total ?? 0, 0, ',', '.') }}</td>
            <td class="cell-data"></td>
        </tr>
        <tr class="bg-gray-headers" style="background-color: #4682B4; color: white;">
            <td colspan="3" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #4682B4;">2.4.2.2- DETALLE INDEMNIZACIONES NO PAGADAS ANOS DE SERVICIO</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Año Servicio'] ?? []); @endphp
    </table>

    <!-- 2.4.3 -->
    <div class="header-blue-small mt-1" style="background-color: #4682B4; border-color: #4682B4;">2.4.3 - INDEMNIZACIONES FERIADO</div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="33%">N° TRABS CON PAGO</td>
            <td width="34%">MONTO PAGADO</td>
            <td width="33%">NO PAGADAS</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->fin_feriado_trabajadores ?? 0 }}</td>
            <td class="cell-data">$ {{ number_format($carpeta->fin_feriado_total ?? 0, 0, ',', '.') }}</td>
            <td class="cell-data"></td>
        </tr>
        <tr class="bg-gray-headers" style="background-color: #4682B4; color: white;">
            <td colspan="3" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #4682B4;">2.4.3.2- DETALLE INDEMNIZACIONES NO PAGADAS FERIADO</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Feriado'] ?? []); @endphp
    </table>

    <!-- 2.4.4 -->
    <div class="header-blue-small mt-1" style="background-color: #4682B4; border-color: #4682B4;">2.4.4 - INDEMNIZACIONES POR FINIQUITO</div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="33%">N° TRABS CON PAGO</td>
            <td width="34%">MONTO PAGADO</td>
            <td width="33%">NO PAGADAS</td>
        </tr>
        <tr>
            <td class="cell-data">0</td>
            <td class="cell-data">$ 0</td>
            <td class="cell-data"></td>
        </tr>
        <tr class="bg-gray-headers" style="background-color: #4682B4; color: white;">
            <td colspan="3" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #4682B4;">2.4.4.2- DETALLE INDEMNIZACIONES NO PAGADAS POR FINIQUITO</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Finiquito'] ?? []); @endphp
        
        <tr class="bg-gray-headers" style="background-color: #4682B4; color: white;">
            <td colspan="3" class="text-left" style="font-size: 9px; padding: 3px 5px; border-color: #4682B4;">2.4.4.3- DETALLE INDEMNIZACIONES RESERVA DE DERECHO</td>
        </tr>
        @php renderDetalleContingencia($contingenciasPorClasif['Reserva de derecho'] ?? []); @endphp
    </table>


    <!-- SECTION 3 -->
    <div class="header-blue-small mt-1">3.- ANTECEDENTES DE LA EMPRESA PRINCIPAL</div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="30%">RUT</td>
            <td width="70%">RAZON SOCIAL / NOMBRE / CONTRATO</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->vinculacion->unidadOrganizacional->mandante->rut ?? '...' }}</td>
            <td class="cell-data">{{ $carpeta->vinculacion->unidadOrganizacional->mandante->razon_social ?? '-' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td>RUT REPRESENTANTE LEGAL</td>
            <td>REPRESENTANTE LEGAL</td>
        </tr>
        <tr>
            <td class="cell-data">{{ $carpeta->vinculacion->unidadOrganizacional->mandante->rut_representante_legal ?? '...' }}</td>
            <td class="cell-data">{{ strtoupper($carpeta->vinculacion->unidadOrganizacional->mandante->representante_legal ?? '...') }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td colspan="2">DOMICILIO</td>
        </tr>
        <tr>
            <td class="cell-data" colspan="2">{{ $carpeta->vinculacion->unidadOrganizacional->mandante->direccion ?? '-' }}</td>
        </tr>
        <tr class="bg-gray-headers">
            <td>PAIS</td>
            <td>REGION &nbsp;&nbsp;&nbsp;&nbsp; COMUNA</td>
        </tr>
        <tr>
            <td class="cell-data">CHILE</td>
            <td class="cell-data">{{ $carpeta->vinculacion->unidadOrganizacional->mandante->comuna->region->nombre ?? '-' }} &nbsp;&nbsp;&nbsp;&nbsp; {{ $carpeta->vinculacion->unidadOrganizacional->mandante->comuna->nombre ?? '-' }}</td>
        </tr>
    </table>

    <!-- SECTION 4 -->
    <div class="header-blue-small mt-1">4.- OBJETIVO DEL CERTIFICADO</div>
    <table class="mb-2">
        <tr class="bg-gray-headers">
            <td width="33%">CURSAR ESTADOS DE PAGO</td>
            <td width="34%">DEVOLUCION DE GARANTIA</td>
            <td width="33%">CUMPLIMIENTO DE OBLIGACIONES</td>
        </tr>
    </table>

    <!-- SECTION 5 -->
    <div class="header-blue-small mt-1">5.- PERIODO CERTIFICADO Y AMBITO DE VALIDEZ</div>
    <p class="intro-text text-bold" style="margin-top: 5px;">
        El presente certificado cubre exclusivamente la obra, empresa o faena señalada en el punto 2 anterior y por el periodo comprendido entre el 1 de {{ strtoupper($carpeta->nombre_mes) }} y el ultimo día del mes de {{ strtoupper($carpeta->nombre_mes) }} de {{ $carpeta->anio }}, siendo válido en todo el territorio nacional.
    </p>

    <!-- SECTION 6 -->
    <div class="header-blue-small mt-1">6.- REQUISITO DE VALIDEZ</div>
    <p class="intro-text text-bold" style="margin-top: 5px;">
        El presente certificado tiene validez solo con logo, folio único, firmas de Jefe de Operaciones y Fiscal o previa verificación en el Sistema de Administración de Contratistas vía Internet.-
    </p>

    <!-- SECTION 7 -->
    <div class="header-blue-small mt-1">7.- OBSERVACIÓN FINAL</div>
    <p class="intro-text text-bold" style="margin-top: 5px;">
        La empresa principal podrá verificar que los datos consignados en el presente certificado, basado en la solicitud de certificado, declaración jurada y documentación laboral y previsional, presentada por el contratista individualizado, corresponda con la realidad existente donde se prestan los servicios o se ejecutan las obras contratadas.
    </p>


    <p style="font-size: 9px; font-weight: bold; margin-top: 20px;">
        Certificado emitido con fecha {{ $carpeta->fecha_envio ? $carpeta->fecha_envio->format('d-m-Y') : '___-___-____' }}
    </p>
    <p style="font-size: 9px; font-weight: bold; margin-top: 10px;">
        Fecha de impresión {{ date('d-m-Y') }}
    </p>


    <table width="100%" style="page-break-inside: avoid; margin-top: 50px; border: none;">
        <tr>
            <td width="50%" class="text-center no-border" style="vertical-align: bottom;">
                <div style="width: 200px; border-bottom: 1px solid #000; margin: 0 auto; height: 60px;"></div>
                <br>
                <div class="text-bold text-uppercase">Jefe de Operaciones</div>
            </td>
            <td width="50%" class="text-center no-border" style="vertical-align: bottom;">
                <div style="width: 200px; border-bottom: 1px solid #000; margin: 0 auto; height: 60px;"></div>
                <br>
                <div class="text-bold text-uppercase">Gerente Legal</div>
            </td>
        </tr>
    </table>


    <div style="page-break-before: always;"></div>

    <!-- SEGUNDA PÁGINA (NOMINA) -->
    <p style="font-size: 9px; font-weight: bold; margin-top: 10px;">
        Fecha de impresión {{ date('d-m-Y') }}
    </p>

    <table width="100%" style="margin-top: 30px; border: none;">
        <tr>
            <td width="50%" class="text-center no-border" style="vertical-align: bottom;">
                <div style="width: 200px; border-bottom: 1px solid #000; margin: 0 auto; height: 60px;"></div>
                <br>
                <div class="text-bold text-uppercase" style="font-size: 9px;">Jefe de Operaciones</div>
            </td>
            <td width="50%" class="text-center no-border" style="vertical-align: bottom;">
                <div style="width: 200px; border-bottom: 1px solid #000; margin: 0 auto; height: 60px;"></div>
                <br>
                <div class="text-bold text-uppercase" style="font-size: 9px;">Gerente Legal</div>
            </td>
        </tr>
    </table>


    <table class="mb-2" style="margin-top: 40px;">
        <tr class="header-blue tbl-nomina" style="background-color: #0073a8;">
            <td colspan="6" style="border-color: #0073a8;">LISTADO DE TRABAJADORES</td>
        </tr>
        <tr class="header-blue-small" style="background-color: #2D8EBB;">
            <td width="5%" rowspan="2" style="border-color: #55a8cf; vertical-align: middle;">N°</td>
            <td width="15%" rowspan="2" style="border-color: #55a8cf; vertical-align: middle;">RUT</td>
            <td width="40%" rowspan="2" style="border-color: #55a8cf; vertical-align: middle;">NOMBRE TRABAJADOR</td>
            <td width="15%" rowspan="2" style="border-color: #55a8cf; vertical-align: middle;">F. CONTRATO</td>
            <td width="25%" colspan="2" style="border-color: #55a8cf;">DESVINCULACIÓN</td>
        </tr>
        <tr class="header-blue-small" style="background-color: #2D8EBB;">
            <td style="border-color: #55a8cf;">Motivo</td>
            <td style="border-color: #55a8cf;">Fecha</td>
        </tr>
        
        @php $i = 1; @endphp
        @foreach($trabajadores as $ctv)
            <tr>
                <td class="cell-data">{{ $i++ }}</td>
                <td class="cell-data">{{ $ctv->vinculacion->trabajador->rut ?? $ctv->snapshot_rut ?? '-' }}</td>
                <td class="cell-data" style="text-align: left;">{{ $ctv->vinculacion->trabajador->nombre_completo ?? $ctv->snapshot_nombres ?? '-' }}</td>
                <td class="cell-data">{{ ($ctv->vinculacion && $ctv->vinculacion->fecha_inicio_contrato) ? \Carbon\Carbon::parse($ctv->vinculacion->fecha_inicio_contrato)->format('d-m-Y') : ($ctv->snapshot_fecha_contrato ? \Carbon\Carbon::parse($ctv->snapshot_fecha_contrato)->format('d-m-Y') : '-') }}</td>
                <td class="cell-data">-</td>
                <td class="cell-data">{{ ($ctv->vinculacion && $ctv->vinculacion->fecha_fin_contrato) ? \Carbon\Carbon::parse($ctv->vinculacion->fecha_fin_contrato)->format('d-m-Y') : '-' }}</td>
            </tr>
        @endforeach
    </table>

</body>
</html>
