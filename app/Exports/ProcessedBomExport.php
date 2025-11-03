<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProcessedBomExport implements FromCollection, WithHeadings
{
    protected $boms;
    protected $plant;

    public function __construct(array $boms, string $plant)
    {
        $this->boms = $boms;
        $this->plant = $plant;
    }

    public function collection(): Collection
    {
        if (empty($this->boms)) {
            return collect([]);
        }

        $excelRows = [];
        $isFirstBom = true;

        foreach ($this->boms as $bom) {
            // Tambahkan baris kosong sebagai pemisah antar BOM
            if (!$isFirstBom) {
                $excelRows[] = array_fill_keys($this->headings(), null);
            }
            $isFirstBom = false;

            $parent = $bom['parent'];
            $components = $bom['components'];
            $itemNumber = 0;

            // Terjemahkan penanda #NOT_FOUND# untuk parent
            $parentCode = ($parent['code'] === '#NOT_FOUND#') ? 'KODE TIDAK DITEMUKAN' : $parent['code'];

            foreach ($components as $comp) {
                $itemNumber += 10;

                // Ambil kode material mentah (raw) dari komponen
                $compCodeRaw = $comp['code'] ?? '';
                // Terjemahkan penanda #NOT_FOUND# untuk tampilan Excel
                $compCode = ($compCodeRaw === '#NOT_FOUND#') ? 'KODE TIDAK DITEMUKAN' : $compCodeRaw;

                // === [PERBAIKAN LOGIKA LGORT SESUAI PERMINTAAN] ===
                // Aturan: Untuk komponen (child), gunakan 'sloc'.
                // Jika 'sloc' kosong, baru gunakan 'sloc1'.
                $compSloc = $comp['sloc'] ?? '';
                $compSloc1 = $comp['sloc1'] ?? '';
                $lgort = !empty($compSloc) ? $compSloc : $compSloc1;
                // === [AKHIR PERBAIKAN LOGIKA] ===

                $excelRows[] = [
                    'RC29N-MATNR'   => $parentCode,
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
                    'RC29P-IDNRK'   => $compCode, // Tampilkan kode yang sudah diterjemahkan
                    'RC29P-KTEXT'   => $comp['description'],
                    'RC29P-MENGE'   => $comp['qty'],
                    'RC29P-MEINS'   => $comp['uom'],
                    'RC29P-AUSCH'   => '',
                    // Gunakan $lgort yang sudah dihitung
                    'RC29P-LGORT'   => $lgort,
                    'RC29P-POTX1'   => '',
                    'RC29P-POTX2'   => '',
                ];
            }
        }
        return collect($excelRows);
    }

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

