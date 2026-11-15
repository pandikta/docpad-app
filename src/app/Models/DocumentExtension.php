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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
