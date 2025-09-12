<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Class pembantu ini memberitahu Maatwebsite/Excel
 * untuk membaca file Excel dengan baris pertama sebagai header (nama kolom).
 */
class HeadingRowImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Tidak perlu ada logika di sini.
        // Data akan diambil dan diproses langsung oleh controller.
    }
}
