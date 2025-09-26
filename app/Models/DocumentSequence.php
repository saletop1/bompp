<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    // Kita tidak perlu 'use HasFactory;' jika tidak menggunakannya

    /**
     * 1. Primary Key adalah 'prefix'.
     */
    protected $primaryKey = 'prefix';

    /**
     * 2. Tipe Primary Key-nya adalah string.
     */
    protected $keyType = 'string';

    /**
     * 3. Primary Key ini TIDAK auto-increment.
     */
    public $incrementing = false;

    /**
     * 4. NONAKTIFKAN TIMESTAMPS (created_at & updated_at).
     * INI ADALAH SOLUSI UNTUK ERROR ANDA SAAT INI.
     */
    public $timestamps = false;

    /**
     * 5. Kolom yang boleh diisi massal.
     */
    protected $fillable = ['prefix', 'last_sequence'];
}
