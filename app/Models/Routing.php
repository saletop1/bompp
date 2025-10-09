<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Routing extends Model {
    use HasFactory;

    protected $fillable = [
        'document_number',
        'document_name',
        'product_name',
        'material',
        'plant',
        'description',
        'header',
        'operations',
        'uploaded_to_sap_at',
        'status',
        'notification_sent', // <-- Tambahkan ini
    ];
}
