<?php

namespace App\Console\Commands;

use App\Exports\AuditLogsExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;

class ExportAndPurgeAuditLogs extends Command
{
    protected $signature = 'audit:export-and-purge';
    protected $description = 'Genera reporte Excel de logs diarios, lo envía por correo y limpia la tabla.';

    public function handle()
    {
        if (!\App\Services\AuditSettingService::isEnabled()) {
            $this->info('El sistema de auditoría está desactivado.');
            return;
        }

        $yesterday = now()->subDay()->startOfDay();
        $today = now();

        $logs = Activity::where('log_name', 'audit')
            ->whereBetween('created_at', [$yesterday, $today])
            ->get();

        if ($logs->isEmpty()) {
            $this->info('No hay logs para exportar hoy.');
            return;
        }

        $filename = 'audit_logs_' . now()->format('Y-m-d') . '.xlsx';
        $path = 'exports/' . $filename;

        // Asegurar que el directorio existe
        if (!Storage::disk('local')->exists('exports')) {
            Storage::disk('local')->makeDirectory('exports');
        }

        // Generar Excel
        Excel::store(new AuditLogsExport($logs), $path, 'local');

        // Enviar por correo a los destinatarios configurados
        $emails = \App\Services\AuditSettingService::getEmails();
        
        if (empty($emails)) {
            $this->warn('No hay correos configurados para el reporte de auditoría.');
        } else {
            $fullPath = Storage::disk('local')->path($path);

            if (!file_exists($fullPath)) {
                $this->error("El archivo no pudo ser generado en: {$fullPath}");
                return;
            }

            Mail::raw("Se adjunta el log de auditoría del día " . now()->format('d/m/Y'), function ($message) use ($emails, $fullPath, $filename) {
                $message->to($emails)
                    ->subject('Log de Auditoría OVAL - ' . now()->format('d/m/Y'))
                    ->attach($fullPath, [
                        'as' => $filename,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });

            $this->info('Reporte enviado a: ' . implode(', ', $emails));
        }

        // Limpiar logs antiguos
        Activity::where('log_name', 'audit')
            ->where('created_at', '<', $today)
            ->delete();

        $this->info('Logs diarios purgados.');
        
        // Opcional: eliminar el archivo local
        Storage::disk('local')->delete($path);
    }
}
