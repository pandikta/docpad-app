<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamRequest extends Model
{
    protected $table = 'trx_team_requests';

    protected $fillable = [
        'team_code',
        'team_name',
        'max_member',
        'description',
        'company_email',
        'requester_name',
        'requester_email',
        'password',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'created_team_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'max_member' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'created_team_id');
    }
}
