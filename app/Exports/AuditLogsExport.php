<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Activitylog\Models\Activity;

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha/Hora',
            'Usuario',
            'Acción',
            'IP',
            'Módulo/URL',
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('d/m/Y H:i:s'),
            $log->causer ? $log->causer->name : 'Sistema',
            $log->description,
            $log->getExtraProperty('ip'),
            $log->getExtraProperty('url') ?? $log->getExtraProperty('route'),
        ];
    }
}
