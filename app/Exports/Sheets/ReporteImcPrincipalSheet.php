<?php

namespace App\Exports\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Mandante;
use App\Models\ReglaDocumental;

class ReporteImcPrincipalSheet implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    protected $mandante;
    protected $soloActivas;

    public function __construct(Mandante $mandante, bool $soloActivas)
    {
        $this->mandante = $mandante;
        $this->soloActivas = $soloActivas;
    }

    public function view(): View
    {
        $query = ReglaDocumental::with(['tipoEntidadControlada', 'nombreDocumento', 'tipoVencimiento'])
            ->where('mandante_id', $this->mandante->id);

        if ($this->soloActivas) {
            $query->where('is_active', true);
        }

        $reglas = $query->get()->sortBy(function ($regla) {
            $entidad = $regla->tipoEntidadControlada->nombre_entidad ?? 'Z';
            $imc = $regla->imc ?? 0;
            return $entidad . '_' . sprintf('%06.4f', 1000 - $imc);
        });

        $dataArray = [];
        foreach ($reglas as $r) {
            $imc = $r->imc;
            $cargasAnio = $imc !== null ? $imc * 12 : null;
            $mesesVigencia = $imc !== null && $imc > 0 ? (1 / $imc) : null;

            $dataArray[] = [
                'Entidad' => $r->tipoEntidadControlada->nombre_entidad ?? 'N/A',
                'Documento' => $r->nombreDocumento->nombre ?? 'N/A',
                'Tipo Vencimiento' => $r->tipoVencimiento->nombre ?? 'N/A',
                'Días Validez (Aut)' => $r->dias_validez_documento ?? 'N/A',
                'Meses Estimados (Man)' => $r->imc_meses_estimados ?? 'N/A',
                'Meses Vigencia Final' => $mesesVigencia ? round($mesesVigencia, 2) : 'N/A',
                'Cargas/Año' => $cargasAnio !== null ? number_format($cargasAnio, 2, ',', '.') : 'N/A',
                'IMC (docs/mes)' => $imc !== null ? number_format($imc, 4, ',', '.') : 'N/A',
                'Estado' => $r->is_active ? 'Activa' : 'Inactiva',
            ];
        }

        return view('reports.excel.imc-principal', [
            'data' => $dataArray,
            'mandante' => $this->mandante
        ]);
    }

    public function title(): string
    {
        $titulo = substr($this->mandante->razon_social, 0, 31);
        $titulo = str_replace(['*', ':', '/', '\\', '?', '[', ']'], '_', $titulo);
        return $titulo;
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }
}
