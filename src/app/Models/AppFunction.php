<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppFunction extends Model
{
    protected $table = 'mst_functions';

    protected $fillable = [
        'code',
        'name',
        'group',
        'description',
        'default_allowed',
    ];

    protected function casts(): array
    {
        return [
            'default_allowed' => 'boolean',
        ];
    }

    public function auths(): HasMany
    {
        return $this->hasMany(FunctionAuth::class, 'function_id');
    }
}
