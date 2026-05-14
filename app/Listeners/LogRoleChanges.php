<?php

namespace App\Listeners;

use Spatie\Permission\Events\RoleAssigned;
use Spatie\Permission\Events\RoleRemoved;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class LogRoleChanges
{
    /**
     * Manejar la asignación de roles.
     */
    public function handleRoleAssigned(RoleAssigned $event): void
    {
        $this->logSecurityAlert('asignado', $event->roleName, $event->user);
    }

    /**
     * Manejar la eliminación de roles.
     */
    public function handleRoleRemoved(RoleRemoved $event): void
    {
        $this->logSecurityAlert('removido', $event->roleName, $event->user);
    }

    /**
     * Registro centralizado de la alerta de seguridad.
     */
    private function logSecurityAlert($accion, $rol, $targetUser)
    {
        $ejecutor = Auth::user();
        
        AuditService::log(
            'seguridad-alerta',
            "Cambio de privilegios detectado: Rol [$rol] $accion al usuario [" . $targetUser->email . "].",
            [
                'contexto' => 'CAMBIO_ROLES',
                'rol_afectado' => $rol,
                'accion' => $accion,
                'usuario_objetivo' => $targetUser->email,
                'ejecutado_por' => $ejecutor ? $ejecutor->email : 'Sistema/CLI'
            ]
        );
    }
}
