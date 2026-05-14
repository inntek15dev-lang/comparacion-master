<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class ExcelDateFormatRule implements ValidationRule
{
    /**
     * El formato de fecha esperado.
     *
     * @var string
     */
    protected $format;

    /**
     * Crea una nueva instancia de la regla.
     *
     * @param  string  $format
     * @return void
     */
    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * Ejecuta la regla de validación.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Excel a veces envía fechas como números (serial date).
        // Si es numérico, lo convertimos a un objeto de fecha de PHP.
        if (is_numeric($value)) {
            try {
                // La función `excelToDateTimeObject` es el estándar para esta conversión.
                $value = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format($this->format);
            } catch (\Exception $e) {
                $fail('El valor de la fecha de Excel no es válido.');
                return;
            }
        }

        // Ahora, validamos que el valor (ya sea el original o el convertido)
        // tenga el formato correcto.
        try {
            $date = Carbon::createFromFormat($this->format, $value);
            if ($date->format($this->format) !== $value) {
                $fail('La fecha en :attribute no coincide con el formato esperado ' . strtoupper($this->format) . '.');
            }
        } catch (\Exception $e) {
            $fail('La fecha en :attribute debe tener el formato ' . strtoupper($this->format) . '.');
        }
    }
}