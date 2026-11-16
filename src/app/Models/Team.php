<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $table = 'mst_teams';

    protected $fillable = [
        'team_code',
        'team_name',
        'max_member',
        'description',
        'is_active',
        'allow_multiple_leaders',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_multiple_leaders' => 'boolean',
        ];
    }

    /**
     * Members of this team.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Documents assigned to this team.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'doc_file_teams', 'team_id', 'document_id')
            ->withTimestamps();
    }
}
