<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunctionAuth extends Model
{
    protected $table = 'mst_function_auths';

    protected $fillable = [
        'function_id',
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

    public function appFunction(): BelongsTo
    {
        return $this->belongsTo(AppFunction::class, 'function_id');
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
