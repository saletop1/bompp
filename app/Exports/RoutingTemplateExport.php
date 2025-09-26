<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RoutingTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect($this->data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Header ini harus cocok dengan template routing Anda
        return [
            'Material',
            'Plant',
            'Description',
            'Usage',
            'Status',
            'Grp Ctr',
            'Operation',
            'Work Cntr',
            'Ctrl Key',
            'Descriptions',
            'Base Qty',
            'UoM',
            'Activity 1',
            'UoM 1',
            'Activity 2',
            'UoM 2',
            'Activity 3',
            'UoM 3',
            'Activity 4',
            'UoM 4',
            'Activity 5',
            'UoM 5',
            'Activity 6',
            'UoM 6',
        ];
    }
}
