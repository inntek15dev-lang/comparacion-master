<?php

namespace App\Livewire\Traits;

trait FormatsDocumentTooltips
{
    /**
     * Formatea el string del motivo de rechazo para un tooltip limpio.
     * @param string|null $motivo
     * @return string
     */
    public function formatarMotivoRechazo(?string $motivo): string
    {
        if (empty($motivo)) {
            return 'Motivo no especificado.';
        }

        $criteriosTexto = 'No cumple con:';
        
        if (str_contains($motivo, 'No cumple con:')) {
            $criteriosPart = explode('No cumple con:', $motivo, 2)[1];
            // Limpia espacios y elimina elementos vacíos que puedan resultar de comas extra
            $criteriosArray = array_filter(array_map('trim', explode(',', $criteriosPart)));
            
            foreach ($criteriosArray as $criterio) {
                // Se usa \n para el salto de línea, que los navegadores interpretan en el atributo title.
                $criteriosTexto .= "\n- " . htmlspecialchars($criterio, ENT_QUOTES, 'UTF-8'); 
            }
            return $criteriosTexto;
        }
        
        // Si no encuentra el formato esperado, devuelve el motivo original de forma segura.
        return htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
    }
}