<?php

namespace App\Services;

/**
 * IaCamposDisponibles — Catálogo fijo de columnas de documentos_cargados
 * que la IA puede extraer e interpretar.
 *
 * Estos son los únicos campos disponibles para seleccionar en la configuración IA.
 * La Regla Documental NO sabe que existe este sistema.
 */
class IaCamposDisponibles
{
    /**
     * Lista canónica de campos extraíbles.
     *
     * Formato:
     *   'campo_clave' => [
     *     'etiqueta'      => texto legible para UI,
     *     'tipo_dato'     => texto|fecha|rut|numero,
     *     'mapea_columna' => columna real en documentos_cargados (null = solo comparación),
     *     'descripcion'   => hint para el prompt de la IA,
     *   ]
     */
    public const CAMPOS = [
        'fecha_emision' => [
            'etiqueta'      => 'Fecha de Emisión',
            'tipo_dato'     => 'fecha',
            'mapea_columna' => 'fecha_emision',
            'descripcion'   => 'Fecha en que fue emitido o generado el documento. Formato YYYY-MM-DD.',
        ],
        'fecha_vencimiento' => [
            'etiqueta'      => 'Fecha de Vencimiento',
            'tipo_dato'     => 'fecha',
            'mapea_columna' => 'fecha_vencimiento',
            'descripcion'   => 'Fecha en que vence o expira el documento. Formato YYYY-MM-DD.',
        ],
        'periodo' => [
            'etiqueta'      => 'Período (YYYY-MM)',
            'tipo_dato'     => 'texto',
            'mapea_columna' => 'periodo',
            'descripcion'   => 'Mes y año al que corresponde el documento. Formato YYYY-MM. Ej: 2026-04.',
        ],
        'entidad_rut' => [
            'etiqueta'      => 'RUT del Titular',
            'tipo_dato'     => 'rut',
            'mapea_columna' => null, // No escribe en doc, solo compara con entidad.rut
            'descripcion'   => 'RUT de la persona o empresa a la que pertenece el documento. Incluir puntos y guión. Ej: 12.345.678-9.',
        ],
    ];

    /**
     * Retorna el catálogo completo como colección de arrays con la clave incluida.
     */
    public static function todos(): array
    {
        return collect(self::CAMPOS)->map(function ($def, $clave) {
            return array_merge(['campo_clave' => $clave], $def);
        })->values()->all();
    }

    /**
     * Retorna la definición de un campo específico o null si no existe.
     */
    public static function definicion(string $campoClave): ?array
    {
        return self::CAMPOS[$campoClave] ?? null;
    }

    /**
     * Retorna las claves disponibles como array simple.
     */
    public static function claves(): array
    {
        return array_keys(self::CAMPOS);
    }
}
