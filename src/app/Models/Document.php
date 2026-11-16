<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Document extends Model
{
    protected $table = 'doc_files';

    protected $fillable = [
        'user_id',
        'folder_id',
        'name',
        'visibility',
        'is_encrypted',
        'content',
        'file_path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function decryptedContent(): string
    {
        $raw = (string) ($this->content ?? '');
        if (! $this->is_encrypted || $raw === '') {
            return $raw;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return $raw;
        }
    }

    public function isStoredFile(): bool
    {
        return ! empty($this->file_path);
    }

    /**
     * Scope query dokumen berdasarkan user:
     * - File `private` HANYA bisa dilihat oleh pembuatnya (bahkan Superadmin tidak bisa melihat milik user lain).
     * - File `publish` bisa dilihat semua tim (Superadmin) atau tim yang di-assign di `doc_file_teams`.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('visibility', '!=', 'private')
                  ->orWhere('user_id', $user->id);
            });
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where(function (Builder $priv) use ($user) {
                $priv->where('visibility', 'private')
                     ->where('user_id', $user->id);
            });

            if ($user->team_id) {
                $q->orWhere(function (Builder $pub) use ($user) {
                    $pub->where('visibility', '!=', 'private')
                        ->whereHas('teams', fn (Builder $t) => $t->where('mst_teams.id', $user->team_id));
                });
            }
        });
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
              ->orWhere(function (Builder $sub) use ($keyword) {
                  $sub->where('is_encrypted', false)
                      ->where('content', 'like', "%{$keyword}%");
              })
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

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'doc_file_favorites', 'document_id', 'user_id')
            ->withPivot('created_at');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DocumentLog::class, 'document_id')->latest('id');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(DocumentShareLink::class, 'document_id')->latest('id');
    }

    /**
     * Bentuk data yang dikirim ke frontend.
     *
     * @return array<string, mixed>
     */
    public function toListItem(): array
    {
        $activeShare = $this->shareLinks->first(fn (DocumentShareLink $link) => $link->isValid());

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'folder_id' => $this->folder_id,
            'folder_name' => $this->folder?->name,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'share_link' => $activeShare ? [
                'id' => $activeShare->id,
                'created_by' => $activeShare->created_by,
                'url' => route('share.show', $activeShare->token),
                'expires_at' => $activeShare->expires_at?->toIso8601String(),
                'has_password' => $activeShare->hasPassword(),
            ] : null,
            'name' => $this->name,
            'visibility' => $this->visibility ?? 'publish',
            'is_encrypted' => (bool) ($this->is_encrypted ?? false),
            'is_stored_file' => $this->isStoredFile(),
            'mime_type' => $this->mime_type,
            'preview_url' => $this->isStoredFile() ? route('documents.preview', $this->id) : null,
            'size' => $this->size,
            'created_by' => $this->user?->name ?? 'Unknown',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'content' => $this->isStoredFile() ? null : $this->decryptedContent(),
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
