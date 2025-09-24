<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutingOperation extends Model
{
    use HasFactory;

    protected $table = 'routing_operations';

    /**
     * PERBAIKAN: Mengizinkan kolom dengan nama yang benar untuk diisi.
     */
    protected $fillable = [
        'material_number',
        'plant',
        'description',
    ];

    public $timestamps = true;
}
