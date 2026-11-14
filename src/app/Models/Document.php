<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $table = 'doc_files';

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'size',
    ];

    /**
     * Scope query dokumen berdasarkan user:
     * - Superadmin dikecualikan dari filter tim (melihat seluruh dokumen).
     * - User biasa hanya melihat dokumen yang di-assign ke timnya di tabel doc_file_teams.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if (! $user->team_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('teams', fn (Builder $q) => $q->where('mst_teams.id', $user->team_id));
    }

    /**
     * Scope pencarian dokumen berdasarkan nama file, isi dokumen, atau nama pembuat.
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);
        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('content', 'like', "%{$keyword}%")
              ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$keyword}%"));
        });
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'doc_file_teams', 'document_id', 'team_id')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentLog::class, 'document_id')->latest('id');
    }

    /**
     * Bentuk data yang dikirim ke frontend.
     *
     * @return array<string, mixed>
     */
    public function toListItem(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'size' => $this->size,
            'created_by' => $this->user?->name ?? 'Unknown',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'content' => $this->content,
            'teams' => $this->teams->map(fn (Team $team) => [
                'id' => $team->id,
                'name' => $team->team_name,
            ])->values()->all(),
            'logs' => $this->logs->map(fn (DocumentLog $log) => [
                'id' => $log->id,
                'user_name' => $log->user?->name ?? 'Unknown',
                'action' => $log->action,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
