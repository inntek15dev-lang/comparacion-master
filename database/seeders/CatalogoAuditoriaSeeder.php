<?php

namespace Database\Seeders;

use App\Models\CatalogoAuditoriaItem;
use Illuminate\Database\Seeder;

class CatalogoAuditoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar tabla con FK-safe (deshabilitar FK temporalmente)
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CatalogoAuditoriaItem::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ============================================================
        // CONTINGENCIAS — Singular (catálogo por trabajador)
        //                 Plural    (agrupado en certificado)
        // ============================================================
        $contingencias = [

            // 1. Sin liquidación + sin comprobante → adeuda remuneración imponible
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador no presenta liquidación de sueldo, tampoco se presenta comprobante de transferencia bancaria. Adeuda remuneración afecta a cotizaciones previsionales.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador no presenta liquidaciones de sueldo, tampoco se presenta comprobante de transferencia bancaria. Adeuda remuneración afecta a cotizaciones previsionales.',
            ],

            // 2. Sin liquidación + sin comprobante → adeuda remuneración (sin mención imponible)
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador no presenta liquidación de sueldo, tampoco se presenta comprobante de transferencia bancaria. Adeuda remuneración.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador no presenta liquidaciones de sueldo, tampoco se presenta comprobante de transferencia bancaria. Adeuda remuneración.',
            ],

            // 3. Liquidación de otro periodo → adeuda remuneración imponible
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador presenta liquidación de sueldo correspondiente a otro periodo. Adeuda remuneración afecta a cotizaciones previsionales.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador presenta liquidación de sueldo correspondiente a otro periodo. Adeuda remuneración afecta a cotizaciones previsionales.',
            ],

            // 4. Liquidación sin firma, $0 sin justificación de días → adeuda remuneración imponible
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador presenta liquidación de sueldo sin firma, si bien se indica pago $0 no se justifican los días no trabajados. Adeuda remuneración afecta a cotizaciones previsionales.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador presenta liquidación de sueldo sin firma, si bien se indica pago $0 no se justifican los días no trabajados. Adeuda remuneración afecta a cotizaciones previsionales.',
            ],

            // 5. Liquidación sin firma + sin comprobante → adeuda remuneración
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador presenta liquidación de sueldo sin firma, tampoco se presenta comprobante de transferencia bancaria. Adeuda remuneración.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador presenta liquidaciones de sueldo sin firma, tampoco se presenta comprobante de transferencia bancaria. Adeuda remuneración.',
            ],

            // 6. Sin liquidación, días injustificados en planilla AFP → adeuda remuneración imponible
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador no presenta liquidación de sueldo. Se verifica días injustificados en planilla de cotizaciones previsionales. Adeuda remuneración, afecta a cotizaciones previsionales.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador no presenta liquidaciones de sueldo. Se verifica días injustificados en planilla de cotizaciones previsionales. Adeuda remuneración, afecta a cotizaciones previsionales.',
            ],

            // 7. Finiquito firmado sin ratificar → adeuda indemnizaciones
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador presenta finiquito firmado por las partes, el que no se encuentra ratificado ante ministro de fe. Adeuda feriado proporcional, Indemnización por años de servicio o indemnización sustitutiva del aviso previo.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador presenta finiquito firmado por las partes, el que no se encuentra ratificado ante ministro de fe. Adeuda feriado proporcional, Indemnización por años de servicio o indemnización sustitutiva del aviso previo.',
            ],

            // 8. Finiquito ratificado sin cotizaciones acreditadas → posible nulidad despido (Ley 20.194)
            [
                'texto'        => 'Respecto del siguiente trabajador si bien el empleador presenta finiquito firmado y ratificado, no se acredita el pago de cotizaciones previsionales. De acuerdo al monto adeudado situación produciría nulidad del despido. (Ley 20.194)',
                'texto_plural' => 'Respecto de los siguientes trabajadores si bien el empleador presenta finiquito firmado y ratificado, no se acredita el pago de cotizaciones previsionales. De acuerdo al monto adeudado situación produciría nulidad del despido. (Ley 20.194)',
            ],

            // 9. Finiquito en cuotas, se adeuda cuotas siguientes
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador presenta finiquito ratificado acordándose el pago en cuotas. Se acredita el pago de 1 cuota adeudándose las siguientes.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador presenta finiquito ratificado acordándose el pago en cuotas. Se acredita el pago de 1 cuota adeudándose las siguientes.',
            ],

            // 10. Finiquito con reserva de derechos → se adeuda monto reclamado
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador presenta finiquito firmado y/o ratificado el que contiene una reserva de derechos. Se adeuda monto reclamado.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador presenta finiquito firmado y/o ratificado el que contiene una reserva de derechos. Se adeuda monto reclamado.',
            ],

            // 11. Finiquito/comparendo/resolución judicial Art. 161 → adeuda recargo 30%
            [
                'texto'        => 'Respecto del siguiente trabajador se presenta finiquito, acta de comparendo, resolución judicial, no existiendo acuerdo en la causal de término, Art. 161 n°1, Necesidades de la empresa. Adeuda recargo del 30%, sobre monto pagado por concepto de años de servicio.',
                'texto_plural' => 'Respecto de los siguientes trabajadores se presenta finiquito, acta de comparendo, resolución judicial, no existiendo acuerdo en la causal de término, Art. 161 n°1, Necesidades de la empresa. Adeuda recargo del 30%, sobre monto pagado por concepto de años de servicio.',
            ],

            // 12. Informado finiquitado sin finiquito firmado → adeuda indemnizaciones (4c/4d)
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador informa que se encuentra finiquitado en nómina de trabajadores, planilla de cotizaciones, aviso cese AFC, cartas, etc., no se presenta finiquito firmado y/o ratificado, infringe Ley 20.281. Adeuda feriado proporcional, Indemnización por años de servicio o indemnización sustitutiva del aviso previo.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador informa que se encuentran finiquitados en nómina de trabajadores, planilla de cotizaciones, aviso cese AFC, cartas, etc., no se presenta finiquito firmado y/o ratificado, infringe Ley 20.281. Adeuda feriado proporcional, Indemnización por años de servicio o indemnización sustitutiva del aviso previo.',
            ],

            // 13. Acta de comparendo, trabajador no acuerda causal → adeuda indemnizaciones (4e/4f)
            [
                'texto'        => 'Respecto del siguiente trabajador se presenta acta de comparendo en la cual se informa que el trabajador no está de acuerdo con la causal. Adeuda feriado proporcional, Indemnización por años de servicio o indemnización sustitutiva del aviso previo.',
                'texto_plural' => 'Respecto de los siguientes trabajadores se presenta acta de comparendo en la cual se informa que el trabajador no está de acuerdo con la causal. Adeuda feriado proporcional, Indemnización por años de servicio o indemnización sustitutiva del aviso previo.',
            ],

            // 14. No incluido en nómina del periodo, no desvínculo de principal → adeuda indemnizaciones (4j/4k)
            [
                'texto'        => 'Respecto del siguiente trabajador, el empleador no lo incluye en nómina del periodo certificado, estando informado en nómina de periodos anteriores, no se presenta documento que lo desvincule de la principal. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, el empleador no los incluye en nómina del periodo certificado, estando informados en nómina de periodos anteriores, no se presenta documento que los desvincule de la principal. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
            ],

            // 15. Cambio de empleador sin nuevo contrato con antigüedad → adeuda indemnizaciones (5a/5b)
            [
                'texto'        => 'Respecto del siguiente trabajador empleador informa en nómina de trabajador que ha sido desvinculado por cambio de empleador (reconocimiento de antigüedad). No se presenta contrato de trabajo con nuevo empleador reconociendo antigüedad laboral. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
                'texto_plural' => 'Respecto de los siguientes trabajadores empleador informa en nómina de trabajadores que han sido desvinculados por cambio de empleador (reconocimiento de antigüedad). No se presenta contrato de trabajo con nuevo empleador reconociendo antigüedad laboral. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
            ],

            // 16. Cambio de faena, documento sin fecha ni individualización → adeuda indemnizaciones (5c/5d)
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador informa en nómina de trabajadores que ha sido desvinculado por cambio de faena. Se presenta documento que no indica fecha de cese, o individualización de la empresa principal. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador informa en nómina de trabajadores que han sido desvinculados por cambio de faena. Se presenta documento que no indica fecha de cese, o individualización de la empresa principal. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
            ],

            // 17. Cesación de la principal sin anexo → adeuda indemnizaciones (5e/5f)
            [
                'texto'        => 'Respecto del siguiente trabajador, empleador informa en nómina del periodo, que ha sido desvinculado por cesación de la principal. No se presenta anexo que lo respalde. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
                'texto_plural' => 'Respecto de los siguientes trabajadores, empleador informa en nómina del periodo, que han sido desvinculados por cesación de la principal. No se presenta anexo que lo respalde. Adeuda Indemnización por años de servicio, indemnización sustitutiva del aviso previo, feriado proporcional.',
            ],

            // 18. Pago inferior a lo pactado (sueldo/bono/asignación) → adeuda diferencia imponible (6a/6b)
            [
                'texto'        => 'Respecto del siguiente trabajador se paga por concepto de sueldo base, bono, asignación, etc., un monto menor al pactado contractualmente. Adeuda diferencia afecta a cotizaciones previsionales.',
                'texto_plural' => 'Respecto de los siguientes trabajadores se paga por concepto de sueldo base, bono, asignación, etc., un monto menor al pactado contractualmente. Adeuda diferencia afecta a cotizaciones previsionales.',
            ],

            // 19. Sueldo base inferior al ingreso mínimo mensual → adeuda diferencia imponible (6c/6d)
            [
                'texto'        => 'Respecto del siguiente trabajador se paga por concepto de sueldo base un monto menor al ingreso mínimo mensual. Adeuda diferencia afecta a cotizaciones previsionales.',
                'texto_plural' => 'Respecto de los siguientes trabajadores se paga por concepto de sueldo base un monto menor al ingreso mínimo mensual. Adeuda diferencia afecta a cotizaciones previsionales.',
            ],

            // 20. Asignaciones (colación/movilización) inferiores a contrato → adeuda diferencia (6e/6f)
            [
                'texto'        => 'Respecto del siguiente trabajador se paga por concepto de asignación colación, movilización, etc., un monto menor al pactado contractualmente. Adeuda diferencia.',
                'texto_plural' => 'Respecto de los siguientes trabajadores se paga por concepto de asignación colación, movilización, etc., un monto menor al pactado contractualmente. Adeuda diferencia.',
            ],
        ];

        // ============================================================
        // OBSERVACIONES — No contienen "Adeuda..." / doc. incompleta
        // Para observaciones no se requiere texto_plural
        // (se gestionan globalmente para la carpeta, no por trabajador)
        // ============================================================
        $observaciones = [

            // 1. Sin liquidación pero con comprobante bancario (1e/1f)
            'Respecto del siguiente trabajador, empleador no presenta liquidación de sueldo, se adjunta comprobante de transferencia bancaria para acreditar pago de remuneración.',

            // 2. Sin contrato de trabajo (2a/2b)
            'Respecto del siguiente trabajador, empleador no presenta contrato de trabajo, por lo que no es posible determinar correcto pago de remuneraciones y cotizaciones previsionales.',

            // 3. Finiquito con reserva de derechos, monto indeterminado (3i/3j)
            'Respecto del siguiente trabajador, empleador presenta finiquito firmado y/o ratificado el que contiene una reserva de derechos. No es posible determinar monto reclamado.',

            // 4. Muerte del trabajador: sin certificado de defunción ni doc. de pago a heredero (3k/3l)
            'Respecto del siguiente trabajador, si bien se presenta finiquito firmado ante ministro de fe, se indica causal Art. 159 Nº3 Muerte del Trabajador, sin embargo, no se presenta certificado de defunción, como tampoco documento que acredite que el comprobante de pago se encuentra firmado por quien se hizo cargo de los gastos funerarios, su cónyuge o heredero.',

            // 5. Finiquito Art. 161 N°1 con licencia médica vigente posterior (3ñ/3o)
            'Respecto del siguiente trabajador, se presenta finiquito firmado y ratificado, por causal de término Art. 161 n°1, Necesidades de la empresa. Cabe mencionar, que el trabajador mantiene licencia médica con fecha de término posterior a la reflejada en el finiquito.',

            // 6. Finiquito firmado por partes sin ratificar (sin mención de deuda) (3p/3q)
            'Respecto del siguiente trabajador, empleador presenta finiquito firmado por las partes, el que no se encuentra ratificado ante ministro de fe.',

            // 7. Finiquito sin firmar/ratificar con carta a domicilio e IT (4a/4b)
            'Respecto del siguiente trabajador se presenta finiquito sin firmar y/o ratificar, infringe Ley 20.281. Se adjunta carta enviada a domicilio del trabajador e Inspección del Trabajo informando que finiquito se encuentra a disposición del trabajador.',

            // 8. Muerte del trabajador: sin comprobante de pago a heredero (4g)
            'Respecto del siguiente trabajador, se presenta certificado de defunción, sin embargo, no se presenta comprobante de pago por concepto de feriado proporcional que se encuentre firmado por quien se hizo cargo de los gastos funerarios, su cónyuge o heredero.',

            // 9. Sin finiquito, relación laboral inferior a 30 días + carta renuncia (4h/4i)
            'Respecto del siguiente trabajador, no se presenta finiquito firmado por las partes. Relación laboral es inferior a 30 días, se adjunta carta de renuncia voluntaria.',

            // 10. Finiquito en trámite con propuesta y comprobante de aceptación (4l/4m)
            'Respecto del siguiente trabajador, no se presenta finiquito firmado y ratificado. Cabe mencionar, que se adjunta propuesta de finiquito en trámite y comprobante de aceptación.',

            // 11. Sin base de cálculo para trato/bono producción/asignaciones (6g/6h)
            'Respecto del siguiente trabajador no se informa en liquidación de sueldo o documento anexo base de cálculo para acreditar el correcto pago de monto por concepto de trato, bono de producción, asignaciones, etc.',

            // 12. No se paga bono contractual (6i/6j)
            'Respecto del siguiente trabajador no se paga Bono pactado contractualmente. Afecto a cotizaciones previsionales.',

            // 13. Sin pago de gratificación legal Art. 50 CT ni reliquidación (7a/7b)
            'Respecto del siguiente trabajador no se observa pago de gratificación legal de acuerdo al art. 50 del Código del Trabajo, como tampoco se observa reliquidación de este concepto en el mes de abril del año comercial correspondiente.',

            // 14. Cotizaciones en base a monto imponible menor (8a/8b)
            'Respecto del siguiente trabajador se pagan cotizaciones previsionales en base a un monto imponible menor al que corresponde.',

            // 15. Licencia médica: sin pago de Seguro de Cesantía (Ord. 20.506) (9a/9b)
            'Respecto del siguiente trabajador que se encuentra haciendo uso de licencia médica, no se acredita pago por concepto de Seguro de Cesantía (Ord. 20.506).',

            // 16. Licencia médica: sin pago de SIS (Ord. 20.506) (9c/9d)
            'Respecto del siguiente trabajador que se encuentra haciendo uso de licencia médica no acredita pago por concepto de SIS. (Ord. 20.506).',

            // 17. Empleador descuenta porcentaje mayor en cotizaciones previsionales (10a/10b)
            'Respecto del siguiente trabajador el empleador descuenta un porcentaje mayor que el que corresponde por concepto de cotizaciones previsionales.',

            // 18. Monto incorrecto en fondo pensiones/salud, pagando menos en institución (10c/10d)
            'Respecto del siguiente trabajador se descuenta monto incorrecto por concepto de fondo de pensiones, cotización de salud, etc., pagándose un monto menor en institución correspondiente.',

            // 19. No se acredita pago de cotizaciones previsionales (10e/10f)
            'Respecto del siguiente trabajador, no se acredita pago de cotizaciones previsionales.',

            // 20. No se paga reliquidación gratificación mayo-junio (11a/11b)
            'Respecto del siguiente trabajador, empleador no paga por concepto de reliquidación gratificación correspondiente a los periodos mayo y junio del año en curso.',

            // 21. No se paga reliquidación ingreso mínimo mayo-junio (11c/11d)
            'Respecto del siguiente trabajador, empleador no paga por concepto de reliquidación ingreso mínimo correspondiente a los periodos mayo y junio del año en curso.',

            // 22. Sin libro de remuneraciones (12a) — solo forma plural en doc. original
            'Respecto de los siguientes trabajadores, empleador no presenta libro de remuneraciones.',

            // 23. Libro de remuneraciones presentado sin desglose (12b) — solo plural en doc. original
            'Respecto de los siguientes trabajadores, empleador no presenta libro de remuneraciones. Cabe mencionar que el presentado no contiene desglose de los trabajadores ni de las remuneraciones.',

            // 24. Sin anexo de inicio de prestaciones Abastible (13a/13b)
            'Respecto del siguiente trabajador, empleador no presenta anexo de inicio de prestaciones servicios para Abastible.',

            // 25. Sin pago por seguridad social Ley 21.735 (1%) (14a/14b)
            'Respecto del siguiente trabajador, el empleador no acredita el pago por concepto de seguridad social, según lo establecido en la Ley N° 21.735 de Reforma de Pensiones.',
        ];

        // Insertar contingencias
        foreach ($contingencias as $cont) {
            CatalogoAuditoriaItem::create([
                'tipo'        => 'contingencia',
                'texto'       => $cont['texto'],
                'texto_plural'=> $cont['texto_plural'],
                'is_active'   => true,
            ]);
        }

        // Insertar observaciones
        foreach ($observaciones as $obs) {
            CatalogoAuditoriaItem::create([
                'tipo'         => 'observacion',
                'texto'        => $obs,
                'texto_plural' => null,
                'is_active'    => true,
            ]);
        }

        $this->command->info('✅ CatalogoAuditoria: ' . count($contingencias) . ' contingencias + ' . count($observaciones) . ' observaciones cargadas.');
    }
}
