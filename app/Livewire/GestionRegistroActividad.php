<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use App\Services\AuditSettingService;

class GestionRegistroActividad extends Component
{
    use WithPagination;

    // Settings
    public $emails;
    public $export_time;
    public $enabled;

    public function mount()
    {
        $this->emails = AuditSettingService::get('emails', '');
        $this->export_time = AuditSettingService::getExportTime();
        $this->enabled = AuditSettingService::isEnabled();
    }

    public function saveSettings()
    {
        $this->validate([
            'emails' => 'nullable',
            'export_time' => 'required|date_format:H:i',
        ]);

        // Validar que los emails tengan formato correcto si no están vacíos
        if ($this->emails) {
            $emailList = array_map('trim', explode(',', $this->emails));
            foreach ($emailList as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addError('emails', "El correo '{$email}' no es válido.");
                    return;
                }
            }
        }

        AuditSettingService::set('emails', $this->emails);
        AuditSettingService::set('export_time', $this->export_time);
        AuditSettingService::set('enabled', $this->enabled ? '1' : '0');

        session()->flash('message', 'Configuración guardada correctamente.');
    }

    public function runExportNow()
    {
        \Illuminate\Support\Facades\Artisan::call('audit:export-and-purge');
        session()->flash('message', 'Reporte generado y enviado manualmente.');
    }

    public function descargarReporte()
    {
        $logs = Activity::where('log_name', 'audit')
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($logs->isEmpty()) {
            session()->flash('message', 'No hay registros acumulados para descargar.');
            return null;
        }

        \App\Services\AuditService::securityAlert(
            "Exportación del historial de Auditoría y Registro de Actividad",
            "EXPORTE_AUDITORIA",
            ['cantidad_registros' => $logs->count()]
        );

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AuditLogsExport($logs), 
            'audit_logs_snapshot_' . now()->format('Y-m-d_H-i') . '.xlsx'
        );
    }

    public function render()
    {
        $logs = Activity::where('log_name', 'audit')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.gestion-registro-actividad', [
            'logs' => $logs
        ])->layout('layouts.app');
    }
}
