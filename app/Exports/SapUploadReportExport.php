<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SapUploadReportExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return collect($this->results)->map(function ($item) {
            return [
                'Material Code' => ltrim($item['material_code'] ?? 'N/A', '0'),
                'Status' => $item['status'] ?? 'N/A',
                'Message' => $item['message'] ?? 'N/A',
                'Plant' => $item['plant'] ?? session('processed_plant', 'N/A'),
            ];
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Material Code',
            'Status',
            'Message',
            'Plant',
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Membuat header menjadi bold
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        // Memberi warna pada baris berdasarkan status
        foreach ($this->results as $index => $item) {
            $rowNumber = $index + 2; // +2 karena baris data mulai dari row 2 (setelah header)
            if (isset($item['status'])) {
                if ($item['status'] === 'Success') {
                    $sheet->getStyle("A{$rowNumber}:D{$rowNumber}")
                          ->getFill()
                          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                          ->getStartColor()
                          ->setARGB('DFF0D8'); // Hijau muda
                } elseif ($item['status'] === 'Failed') {
                    $sheet->getStyle("A{$rowNumber}:D{$rowNumber}")
                          ->getFill()
                          ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                          ->getStartColor()
                          ->setARGB('F2DEDE'); // Merah muda
                }
            }
        }
    }
}

