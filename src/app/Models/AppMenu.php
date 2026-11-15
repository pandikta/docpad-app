<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppMenu extends Model
{
    protected $table = 'mst_menus';

    protected $fillable = [
        'code',
        'name',
        'group',
        'section',
        'route_name',
        'icon',
        'description',
        'default_allowed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_allowed' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function auths(): HasMany
    {
        return $this->hasMany(MenuAuth::class, 'menu_id');
    }
}
