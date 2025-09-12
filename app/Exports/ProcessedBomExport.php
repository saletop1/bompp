<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProcessedBomExport implements FromCollection, WithHeadings
{
    protected $boms;
    protected $plant;

    /**
     * Constructor sekarang menerima array dari BOM dan juga plant.
     */
    public function __construct(array $boms, string $plant)
    {
        $this->boms = $boms;
        $this->plant = $plant;
    }

    /**
     * Mengubah data multi-level BOM menjadi daftar datar untuk Excel.
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        if (empty($this->boms)) {
            return collect([]);
        }

        $excelRows = [];
        $isFirstBom = true;

        foreach ($this->boms as $bom) {
            // Tambahkan baris kosong sebagai pemisah antar BOM di Excel
            if (!$isFirstBom) {
                $excelRows[] = array_fill_keys($this->headings(), null);
            }
            $isFirstBom = false;

            $parent = $bom['parent'];
            $components = $bom['components'];
            $itemNumber = 0;

            foreach ($components as $comp) {
                $itemNumber += 10; // Increment item (0010, 0020, etc.)
                $excelRows[] = [
                    'RC29N-MATNR'   => $parent['code'],
                    'RC29K-OBKTX'   => $parent['description'],
                    'RC29N-WERKS'   => $this->plant,
                    'RC29N-STLAN'   => '1',
                    'RC29N-STLAL'   => '1',
                    'RC29K-ZTEXT'   => '',
                    'RC29K-STKTX'   => '',
                    'RC29K-BMENG'   => $parent['qty'],
                    'RC29K-BMEIN'   => $parent['uom'],
                    'RC29P-POSNR'   => str_pad($itemNumber, 4, '0', STR_PAD_LEFT),
                    'RC29P-POSTP'   => 'L',
                    'RC29P-IDNRK'   => $comp['code'],
                    'RC29P-KTEXT'   => $comp['description'],
                    'RC29P-MENGE'   => $comp['qty'],
                    'RC29P-MEINS'   => $comp['uom'],
                    'RC29P-AUSCH'   => '',
                    'RC29P-LGORT'   => $comp['sloc'],
                    'RC29P-POTX1'   => '',
                    'RC29P-POTX2'   => '',
                ];
            }
        }
        return collect($excelRows);
    }

    /**
     * Mendefinisikan baris header untuk file export.
     * @return array
     */
    public function headings(): array
    {
        return [
            'RC29N-MATNR', 'RC29K-OBKTX', 'RC29N-WERKS', 'RC29N-STLAN', 'RC29N-STLAL',
            'RC29K-ZTEXT', 'RC29K-STKTX', 'RC29K-BMENG', 'RC29K-BMEIN', 'RC29P-POSNR',
            'RC29P-POSTP', 'RC29P-IDNRK', 'RC29P-KTEXT', 'RC29P-MENGE', 'RC29P-MEINS',
            'RC29P-AUSCH', 'RC29P-LGORT', 'RC29P-POTX1', 'RC29P-POTX2',
        ];
    }
}

