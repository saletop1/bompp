<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MaterialMasterExport implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Mengembalikan data yang akan ditulis ke Excel.
     * @return \Illuminate\Support\Collection
     */
    public function collection(): Collection
    {
        return collect($this->data);
    }

    /**
     * Mendefinisikan baris header untuk file Excel.
     * Secara dinamis mengambil header dari kunci data baris pertama.
     * @return array
     */
    public function headings(): array
    {
        if (empty($this->data)) {
            return [];
        }
        return array_keys($this->data[0]);
    }
}
