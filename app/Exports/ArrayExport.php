<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ArrayExport implements FromCollection, WithHeadings
{
    protected $data;
    protected $headings;

    public function __construct(array $data, array $headings)
    {
        $this->data = $data;
        $this->headings = $headings;
    }

    public function collection()
    {
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return $this->headings;
    }
}