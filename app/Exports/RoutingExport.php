<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RoutingExport implements FromCollection, WithHeadings
{
    protected $boms;
    protected $plant;

    public function __construct(array $boms, string $plant)
    {
        $this->boms = $boms;
        $this->plant = $plant;
    }

    /**
     * Mengubah data BOM yang sudah diproses menjadi format routing.
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        $routingData = [];
        $processedMaterials = []; // Untuk menghindari duplikat

        foreach ($this->boms as $bom) {
            $parent = $bom['parent'];
            // Tambahkan parent jika valid dan belum diproses
            if (!empty($parent['code']) && $parent['code'] !== '#NOT_FOUND#' && !isset($processedMaterials[$parent['code']])) {
                $routingData[] = $this->mapToRoutingRow($parent, $this->plant);
                $processedMaterials[$parent['code']] = true;
            }

            // Tambahkan komponen jika valid dan belum diproses
            foreach ($bom['components'] as $component) {
                if (!empty($component['code']) && $component['code'] !== '#NOT_FOUND#' && !isset($processedMaterials[$component['code']])) {
                    $routingData[] = $this->mapToRoutingRow($component, $this->plant);
                    $processedMaterials[$component['code']] = true;
                }
            }
        }
        return collect($routingData);
    }

    /**
     * Helper untuk memetakan data material ke satu baris routing.
     */
    private function mapToRoutingRow(array $material, string $plant): array
    {
        // Anda bisa menyesuaikan nilai default ini sesuai kebutuhan
        return [
            'Material' => $material['code'],
            'Plant' => $plant,
            'Grp Ctr' => '1',
            'Description' => $material['description'],
            'Usage' => '1',
            'Status' => '4',
            'Operation' => '10', // Contoh nilai default
            'Work Ctr' => 'WC112', // Contoh nilai default
            'Ctrl Key' => 'ZP03', // Contoh nilai default
            'Descriptions' => 'MOULDING', // Contoh nilai default
            'Base Qty' => '1',
            'UoM' => $material['uom'],
            'Activity 1' => '', 'UoM 1' => '',
            'Activity 2' => '', 'UoM 2' => '',
            'Activity 3' => '', 'UoM 3' => '',
            'Activity 4' => '', 'UoM 4' => '',
            'Activity 5' => '', 'UoM 5' => '',
            'Activity 6' => '', 'UoM 6' => '',
            'Purchasing Group' => '',
            'Pln Deliv Time' => '',
            'Price Unit' => '',
        ];
    }

    /**
     * Mendefinisikan baris header untuk file Excel.
     */
    public function headings(): array
    {
        return [
            'Material', 'Plant', 'Grp Ctr', 'Description', 'Usage', 'Status',
            'Operation', 'Work Ctr', 'Ctrl Key', 'Descriptions', 'Base Qty', 'UoM',
            'Activity 1', 'UoM 1', 'Activity 2', 'UoM 2', 'Activity 3', 'UoM 3',
            'Activity 4', 'UoM 4', 'Activity 5', 'UoM 5', 'Activity 6', 'UoM 6',
            'Purchasing Group', 'Pln Deliv Time', 'Price Unit'
        ];
    }
}
