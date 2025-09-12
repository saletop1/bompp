<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Class ini memberitahu Maatwebsite/Excel untuk
 * membaca file dengan baris pertama sebagai header (nama kolom).
 * Secara otomatis, nama header seperti "Material Description" akan diubah
 * menjadi "material_description" yang bisa diakses sebagai kunci array.
 */
class BomImport implements ToCollection, WithHeadingRow
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
