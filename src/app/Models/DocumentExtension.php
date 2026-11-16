<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentExtension extends Model
{
    protected $table = 'mst_extensions';

    protected $fillable = [
        'extension',
        'label',
        'description',
        'max_size_kb',
        'is_active',
        'create_from_form',
    ];

    protected function casts(): array
    {
        return [
            'max_size_kb' => 'integer',
            'is_active' => 'boolean',
            'create_from_form' => 'boolean',
        ];
    }
}
