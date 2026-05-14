<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DocumentosInstruccionesSheet implements WithTitle, FromArray, WithStyles, WithEvents
{
    public function title(): string
    {
        return 'INSTRUCCIONES';
    }

    public function array(): array
    {
        return [
            // FILA 1: Título principal
            ['GUÍA MAESTRA DE MIGRACIÓN Y CARGA MASIVA DE DOCUMENTOS (V8.17)', '', '', ''],

            // FILA 2: Espaciador
            [''],

            // FILA 3: Sección I
            ['I. EL PROCESO DE MIGRACIÓN (PASO A PASO)', '', '', ''],

            // FILA 4-8: Pasos
            ['PASO 1: SUBIR ARCHIVOS FÍSICOS', 'En el módulo "Importar Documentos", seleccione el Principal y el Contratista para habilitar la plantilla.', '', ''],
            ['', 'Haga clic en "Subir Archivos" y arrastre sus PDFs al Repositorio Temporal. Espere a que el contador se actualice.', '', ''],
            ['PASO 2: PREPARACIÓN DE DATOS', 'Complete la hoja "Migración de Documentos" con los datos. Los desplegables ya vienen pre-filtrados.', '', ''],
            ['', 'Cada fila corresponde a un archivo. El "Nombre Archivo Físico" debe coincidir exactamente con el PDF subido.', '', ''],
            ['PASO 3: CARGA DEL EXCEL', 'Suba este archivo Excel al módulo de Importación y haga clic en "Importar Documentos".', '', ''],
            ['PASO 4: PROCESAMIENTO', 'El sistema vinculará los PDFs, los moverá a su ubicación final y creará los registros en la BD.', '', ''],

            // FILA 10: Espaciador
            [''],

            // FILA 11: Sección II
            ['II. MAPA COMPLETO DE CAMPOS (27 COLUMNAS)', '', '', ''],

            // FILA 12: Cabecera de la tabla
            ['#', 'COLUMNA EN EXCEL', 'LLAVE TÉCNICA (BD)', '¿OBLIGATORIO?', 'VALOR POR DEFECTO SI SE OMITE'],

            // FILA 13-39: Datos de la tabla (27 campos)
            ['A', 'Mandante*', 'mandante_id', 'SI ✅', '❌ Fila rechazada'],
            ['B', 'Contratista*', 'contratista_id', 'SI ✅', '❌ Fila rechazada'],
            ['C', 'Unidad Organizacional', 'unidad_organizacional_id', 'NO ⭕', 'NULL (no existía en sistemas obsoletos)'],
            ['D', 'Tipo de Entidad*', 'entidad_type', 'SI ✅', '❌ Fila rechazada'],
            ['E', 'ID/RUT/Patente Entidad*', 'entidad_id (resuelto)', 'SI ✅', '❌ Fila rechazada'],
            ['F', 'Regla Documental*', 'regla_documental_id_origen', 'SI ✅', '❌ Fila rechazada'],
            ['G', 'Nombre Documento (Snapshot)*', 'nombre_documento_snapshot', 'SI ✅', '❌ Fila rechazada'],
            ['H', 'Nombre Archivo Físico*', 'nombre_original_archivo', 'SI ✅', '❌ Fila rechazada'],
            ['I', 'Ruta Destino (Opcional)', 'ruta_archivo', 'NO ⭕', 'Sistema auto-genera: {entidad}s/{id}/{uuid}.pdf'],
            ['J', 'Fecha Emisión', 'fecha_emision', 'NO ⭕', 'NULL'],
            ['K', 'Fecha Vencimiento', 'fecha_vencimiento', 'NO ⭕', 'NULL (doc. sin vencimiento / indefinido)'],
            ['L', 'Periodo', 'periodo', 'NO ⭕', 'NULL (formato YYYY-MM si aplica)'],
            ['M', 'Estado Validación*', 'estado_validacion', 'SI ✅', '❌ Fila rechazada'],
            ['N', 'Resultado Validación', 'resultado_validacion', 'NO ⭕', 'NULL (doc. sin revisión aún)'],
            ['O', 'ID Validador ASEM', 'asem_validador_id', 'NO ⭕', 'ID del usuario de migración (54)'],
            ['P', 'ID Validador Mandante', 'mandante_validador_id', 'NO ⭕', 'NULL'],
            ['Q', 'Fecha Validación General', 'fecha_validacion', 'NO ⭕', 'NULL'],
            ['R', 'Fecha Validación ASEM', 'fecha_validacion_asem', 'NO ⭕', 'NULL'],
            ['S', 'Fecha Validación Mandante', 'fecha_validacion_mandante', 'NO ⭕', 'NULL'],
            ['T', 'Observación Validador', 'observacion_validador', 'NO ⭕', 'NULL'],
            ['U', 'Observación Rechazo', 'observacion_rechazo', 'NO ⭕', 'NULL'],
            ['V', 'Observación Interna ASEM', 'observacion_interna_asem', 'NO ⭕', 'NULL'],
            ['W', 'Motivo Revalidación', 'motivo_revalidacion', 'NO ⭕', 'NULL'],
            ['X', 'Tipo Vencimiento (Snapshot)', 'tipo_vencimiento_snapshot', 'NO ⭕', '"MIGRADO"'],
            ['Y', 'Motivo Modif Vencimiento', 'motivo_modificacion_vencimiento', 'NO ⭕', 'NULL'],
            ['Z', 'Snapshot Criterios (JSON)', 'criterios_snapshot', 'NO ⭕', 'NULL'],
            ['AA', 'Observación Doc (Snapshot)', 'observacion_documento_snapshot', 'NO ⭕', '"MIGRACION HISTORICA"'],

            // FILA 40: Espaciador
            [''],

            // FILA 41: Sección III
            ['III. INSTRUCCIONES DETALLADAS POR CAMPO', '', '', ''],

            // FILA 42: Cabecera
            ['CAMPO', '¿QUÉ PONER?', 'EJEMPLO', 'OBSERVACIONES'],

            // FILA 43-69: Detalle de cada campo
            ['Mandante*', 'Razón social del Mandante tal como aparece en el sistema.', 'PRINCIPAL PR MADESUN SA', 'La lista ya viene filtrada al que seleccionó en pantalla.'],
            ['Contratista*', 'El ID y Razón social del Contratista.', 'ID: 5542 - CONTRATISTA EJEMPLO SPA', 'Aparece con el ID_REGISTRO. Viene filtrado de la pantalla.'],
            ['Unidad Organizacional', 'Formato: MANDANTE — UO PADRE < UO HIJA.', 'PRINCIPAL PRUEBA — GENERAL < TRANSPORTE', 'OPCIONAL. En sistemas obsoletos este dato no existía. Dejar vacío.'],
            ['Tipo de Entidad*', 'Ruta completa del modelo Laravel de la entidad.', 'App\Models\Trabajador', 'Opciones: App\Models\Trabajador, App\Models\Vehiculo, App\Models\Maquinaria, App\Models\Embarcacion'],
            ['ID/RUT/Patente Entidad*', 'Identificador único del recurso en el sistema.', '12345678-9 / AB-1234 / SN001', 'Trabajador=RUT | Vehículo=Patente | Maquinaria=N°Serie | Embarcacion=Matrícula'],
            ['Regla Documental*', 'Formato: MANDANTE — NOMBRE DOCUMENTO.', 'PRINCIPAL PRUEBA — CI CEDULA DE IDENTIDAD', 'Limitada a las reglas del Mandante seleccionado.'],
            ['Nombre Documento (Snapshot)*', 'Nombre descriptivo/histórico del documento.', 'Certificado AFP (Sistema Anterior)', 'Es el nombre que tenía en el sistema viejo. Texto libre.'],
            ['Nombre Archivo Físico*', 'Nombre EXACTO del archivo subido al Repositorio.', 'cedula_juan_perez.pdf', 'Debe coincidir al 100% con el archivo subido en Paso 1.'],
            ['Ruta Destino (Opcional)', 'DEJAR VACÍO (Recomendado).', '', 'El sistema autogestiona la ubicación del archivo por seguridad.'],
            ['Fecha Emisión', 'Fecha de emisión del documento (DD/MM/YYYY o YYYY-MM-DD).', '15/03/2023', 'Si no tiene, dejar vacío.'],
            ['Fecha Vencimiento', 'Fecha de vencimiento (DD/MM/YYYY o YYYY-MM-DD).', '15/03/2024', 'OPCIONAL. Documentos sin vencimiento (ej: cédula) dejar vacío.'],
            ['Periodo', 'Mes/año de aplicación para documentos periódicos.', '2024-03', 'Formato YYYY-MM. Solo para documentos de tipo "Por Periodo".'],
            ['Estado Validación*', 'Estado operacional actual del documento.', 'Revisado (Finalizado)', 'Ver listado en hoja "Listados", columna Estados Validación.'],
            ['Resultado Validación', 'Resultado de la evaluación si fue revisado.', 'Aprobado', 'OPCIONAL. Solo "Aprobado" o "Rechazado". Si no ha sido revisado, dejar vacío.'],
            ['ID Validador ASEM', 'ID numérico del usuario validador de ASEM.', '54', 'Si no se proporciona, se asigna el ID 54 (usuario de migración).'],
            ['ID Validador Mandante', 'ID numérico del usuario validador del Mandante.', '12', 'Si no aplica, dejar vacío.'],
            ['Fecha Validación General', 'Timestamp de la validación general.', '2024-01-15 14:30:00', 'Si no tiene, dejar vacío.'],
            ['Fecha Validación ASEM', 'Timestamp de la validación de ASEM.', '2024-01-14 10:00:00', 'Solo para flujos de doble validación.'],
            ['Fecha Validación Mandante', 'Timestamp de la validación del Mandante.', '2024-01-15 14:30:00', 'Solo para flujos de doble validación.'],
            ['Observación Validador', 'Comentario del validador sobre el documento.', 'Documento legible y vigente', 'Si no tiene, dejar vacío.'],
            ['Observación Rechazo', 'Motivo de rechazo si el resultado fue "Rechazado".', 'Documento ilegible', 'Si no aplica, dejar vacío.'],
            ['Observación Interna ASEM', 'Nota interna del equipo ASEM (no visible al cliente).', 'Verificar en próxima auditoría', 'Si no tiene, dejar vacío.'],
            ['Motivo Revalidación', 'Razón por la que se requirió una revalidación.', 'Cambio de criterio documental', 'Si no aplica, dejar vacío.'],
            ['Tipo Vencimiento (Snapshot)', 'Tipo de vencimiento al momento de la carga.', 'A FECHA', 'Opciones: A FECHA, INDEFERIDO, POR PERIODO, MIGRADO. Default: "MIGRADO".'],
            ['Motivo Modif Vencimiento', 'Razón si se modificó la fecha de vencimiento.', 'Extensión por pandemia', 'Si no aplica, dejar vacío.'],
            ['Snapshot Criterios (JSON)', 'Criterios de evaluación del sistema anterior en JSON.', '{"criterio":"Vigencia","resultado":"OK"}', 'Formato JSON. Si no tiene, dejar vacío.'],
            ['Observación Doc (Snapshot)', 'Observación general del documento.', 'Documento migrado desde OVAL', 'Default: "MIGRACION HISTORICA" si se deja vacío.'],

            // FILA 70: Espaciador
            [''],

            // FILA 71: Sección IV
            ['IV. RESUMEN Y NOTAS TÉCNICAS', '', '', ''],

            // FILA 72-79: Notas
            ['TOTAL OBLIGATORIOS:', '8 campos (marcados con * en ROJO)', '', ''],
            ['TOTAL OPCIONALES:', '19 campos (los demás)', '', ''],
            ['', '', '', ''],
            ['NOTA 1:', 'Las columnas obligatorias (marcadas con *) aparecen con fondo ROJO en la hoja "Migración de Documentos".', '', ''],
            ['NOTA 2:', 'Si no tiene un dato opcional, puede dejarlo vacío o escribir "SIN DATOS/MIGRACION". El sistema lo tratará como NULL.', '', ''],
            ['NOTA 3:', 'Se recomienda cargar máximo 1000 filas por archivo Excel para asegurar estabilidad.', '', ''],
            ['NOTA 4:', 'La plantilla descargada es DINÁMICA: solo mostrará opciones en los desplegables para el Principal y Contratista que usted seleccionó en pantalla.', '', ''],
            ['NOTA 5:', 'La Unidad Organizacional es OPCIONAL porque en sistemas obsoletos este dato no existía. El sistema vincula el documento por Regla Documental.', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Título principal (fila 1)
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true)->setColor(new Color('FF4338CA'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Estilo para secciones (I, II, III, IV)
        $sectionStyle = [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4338CA'], // Indigo-700
            ],
        ];

        foreach ([3, 11, 41, 71] as $sectionRow) {
            $sheet->mergeCells("A{$sectionRow}:E{$sectionRow}");
            $sheet->getStyle("A{$sectionRow}:E{$sectionRow}")->applyFromArray($sectionStyle);
        }

        // Estilo para las cabeceras de tablas (filas 12 y 42)
        $tableHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'], // Navy-900
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A12:E12')->applyFromArray($tableHeaderStyle);
        $sheet->getStyle('A42:D42')->applyFromArray($tableHeaderStyle);

        // Estilo para los datos de la tabla de mapa de campos (filas 13-39)
        $tableBorderStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
            ],
        ];
        $sheet->getStyle('A13:E39')->applyFromArray($tableBorderStyle);
        $sheet->getStyle('A43:D69')->applyFromArray($tableBorderStyle);

        // Resaltar filas obligatorias en verde claro
        $obligatorioStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDCFCE7'], // Green-100
            ],
        ];

        // Resaltar filas opcionales en gris clarito
        $opcionalStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF9FAFB'], // Gray-50
            ],
        ];

        // Filas obligatorias: A(13), B(14), D(16), E(17), F(18), G(19), H(20), M(25)
        $filasObligatorias = [13, 14, 16, 17, 18, 19, 20, 25];
        foreach ($filasObligatorias as $fila) {
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($obligatorioStyle);
        }

        // Filas opcionales: el resto
        $filasOpcionales = [15, 21, 22, 23, 24, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39];
        foreach ($filasOpcionales as $fila) {
            $sheet->getStyle("A{$fila}:E{$fila}")->applyFromArray($opcionalStyle);
        }

        // Estilo para los pasos (filas 4-9)
        $stepStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF0F9FF'], // Sky-50
            ],
        ];
        foreach ([4, 6, 8, 9] as $stepRow) {
            $sheet->getStyle("A{$stepRow}")->applyFromArray($stepStyle);
        }

        // Estilo para notas técnicas (72-79)
        $notaStyle = ['font' => ['bold' => true, 'color' => ['argb' => 'FFB91C1C']]];
        foreach ([72, 73, 75, 76, 77, 78, 79] as $notaRow) {
            $sheet->getStyle("A{$notaRow}")->applyFromArray($notaStyle);
        }

        // Ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(55);

        // Wrap text en columnas largas
        $sheet->getStyle('B1:E79')->getAlignment()->setWrapText(true);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Congelar panel para que las cabeceras sean siempre visibles
                $event->sheet->getDelegate()->freezePane('A3');
            },
        ];
    }
}
