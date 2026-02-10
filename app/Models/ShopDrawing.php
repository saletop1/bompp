<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopDrawing extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_code',
        'plant',
        'description',
        'drawing_type',
        'version',
        'dropbox_file_id',
        'dropbox_path',
        'dropbox_share_url',
        'dropbox_direct_url',
        'filename',
        'original_filename',
        'file_size',
        'file_extension',
        'user_id',
        'uploaded_at',
        'material_type',
        'material_group',
        'base_unit'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function getDrawingTypeNameAttribute()
    {
        $types = [
            // New types
            'assembly' => 'Assembly Drawing',
            'detail' => 'Detail Drawing',
            'exploded' => 'Exploded View',
            'orthographic' => 'Orthographic Drawing (2D)',
            'perspective' => 'Perspective Drawing (3D)',
            'fabrication' => 'Fabrication Drawing',
            // Old types for backward compatibility
            'drawing' => 'Assembly Drawing',
            'technical' => 'Detail Drawing',
            'installation' => 'Exploded View',
            'as_built' => 'Orthographic Drawing (2D)',
            'master' => 'Perspective Drawing (3D)'
        ];
        
        return $types[$this->drawing_type] ?? ucfirst($this->drawing_type);
    }
    
    /**
     * Get the display version (convert "Master" to "Ver.0")
     */
    public function getDisplayVersionAttribute()
    {
        $version = $this->version ?? 'Ver.0';
        return $version === 'Master' ? 'Ver.0' : $version;
    }
    
    /**
     * Scope to check for exact duplicate combinations
     */
    public function scopeExactDuplicate($query, $materialCode, $plant, $drawingType, $version, $filename)
    {
        return $query->where('material_code', $materialCode)
            ->where('plant', $plant)
            ->where('drawing_type', $drawingType)
            ->where('version', $version)
            ->where('original_filename', $filename);
    }
}