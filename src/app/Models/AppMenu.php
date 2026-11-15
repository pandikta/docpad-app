<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppMenu extends Model
{
    protected $table = 'mst_menus';

    protected $fillable = [
        'parent_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function auths(): HasMany
    {
        return $this->hasMany(MenuAuth::class, 'menu_id');
    }
}
