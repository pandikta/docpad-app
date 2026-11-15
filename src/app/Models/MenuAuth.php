<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuAuth extends Model
{
    protected $table = 'mst_menu_auths';

    protected $fillable = [
        'menu_id',
        'team_id',
        'user_id',
        'is_allowed',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(AppMenu::class, 'menu_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
