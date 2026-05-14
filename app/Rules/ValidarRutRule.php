<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Closure; // Importar Closure para Laravel 9+

class ValidarRutRule implements Rule
{
    /**
     * Indica si el RUT debe ser único en una tabla.
     * No se usa directamente en esta regla, pero el constructor lo recibía en tu código original.
     * Lo mantenemos por si tienes alguna lógica que quieras añadir, aunque la unicidad
     * ya la manejas con Rule::unique().
     *
     * @var bool
     */
    // protected $checkUnique;

    /**
     * Create a new rule instance.
     * El parámetro $checkUnique no es necesario para la validación de formato del RUT.
     * Lo comentaré para simplificar, ya que la unicidad se maneja fuera.
     *
     * @return void
     */
    public function __construct(/*$checkUnique = false*/)
    {
        // $this->checkUnique = $checkUnique;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // ================== INICIO DE LA MODIFICACIÓN CANÓNICA (MODO COLOMBIA) ==================
        // Para el despliegue en Colombia, la validación estricta del RUT chileno se desactiva.
        // La regla siempre pasará, permitiendo el ingreso de NITs u otros formatos.
        // Para reactivar la validación chilena, simplemente elimine la siguiente línea
        // y descomente el bloque de código original que se encuentra debajo.
        
        return true;

        /*
        // --- INICIO DEL CÓDIGO ORIGINAL CHILENO (EN HIBERNACIÓN) ---
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $rut = preg_replace('/[^0-9kK]/', '', strtoupper(trim($value)));

        if (strlen($rut) < 2) {
            return false;
        }

        $numero = substr($rut, 0, -1);
        $dv = substr($rut, -1);

        if (!ctype_digit($numero) || strlen($dv) !== 1 || !preg_match('/^[0-9K]$/', $dv)) {
            return false;
        }
        
        if (strlen($numero) > 9) { // Considerar RUTs hasta 99.999.999-K
            return false;
        }

        // Validar que no sean todos ceros o un número demasiado bajo que no es un RUT real.
        // Puedes ajustar este límite si es necesario.
        if ((int)$numero <= 0 || (int)$numero < 50000) { // Ajustar según sea necesario, algunos RUTs bajos son válidos pero raros.
            // return false; // Comentado para permitir RUTs más bajos si es necesario, pero suelen ser problemáticos.
        }


        return $this->calcularDV($numero) == $dv;
        // --- FIN DEL CÓDIGO ORIGINAL CHILENO (EN HIBERNACIÓN) ---
        */
        // ================== FIN DE LA MODIFICACIÓN CANÓNICA ====================
    }

    /**
     * Calcula el dígito verificador de un número de RUT.
     *
     * @param  string  $numero
     * @return string
     */
    protected function calcularDV($numero)
    {
        $numero = preg_replace('/[^0-9]/', '', $numero); // Asegurar solo números
        if (!ctype_digit($numero)) {
            return false; // No debería pasar si se limpió antes, pero por seguridad.
        }

        $suma = 0;
        $multiplo = 2;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $suma += $numero[$i] * $multiplo;
            if ($multiplo == 7) {
                $multiplo = 2;
            } else {
                $multiplo++;
            }
        }

        $resto = $suma % 11;
        $dvCalculado = 11 - $resto;

        if ($dvCalculado == 11) {
            return '0';
        } elseif ($dvCalculado == 10) {
            return 'K';
        } else {
            return (string)$dvCalculado;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'El campo :attribute no tiene un formato de RUT chileno válido.';
    }

    /**
     * Run the validation rule.
     * Para Laravel 9+ que soporta invokable rules con Closure.
     * Si estás en una versión anterior, este método puede no ser necesario o tener otra firma.
     * Sin embargo, el método passes() es el principal.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail($this->message());
        }
    }
}