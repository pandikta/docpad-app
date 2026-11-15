<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'mst_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'email',
        'password',
        'storage_limit_mb',
    ];

    /**
     * The team this user belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Dokumen yang dimiliki oleh user ini.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    /**
     * Cek apakah user ini adalah superadmin.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Cek apakah user ini adalah Team Leader / PM untuk timnya.
     */
    public function isTeamLeader(): bool
    {
        return ! $this->isSuperAdmin() && (bool) $this->is_team_leader && $this->team_id !== null;
    }

    /**
     * Cek apakah user berhak mengakses resource milik team tertentu.
     * Superadmin otomatis dikecualikan (bisa akses semua team).
     */
    public function canAccessTeam(?int $teamId): bool
    {
        return $this->isSuperAdmin() || $this->team_id === $teamId;
    }

    /**
     * Kirim notifikasi reset password dengan template email custom DocPad.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'is_team_leader' => 'boolean',
            'storage_limit_mb' => 'integer',
        ];
    }
}
