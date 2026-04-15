<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthDiagnosticLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auth_diagnostic_logs';

    protected $fillable = [
        'event',
        'level',
        'user_id',
        'app_key_fingerprint',
        'ip_hash',
        'session_id_prefix',
        'remember_requested',
        'remember_effective',
        'recaller_cookie_present',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'remember_requested' => 'boolean',
            'remember_effective' => 'boolean',
            'recaller_cookie_present' => 'boolean',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', 'like', $event.'%');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function levelBadgeClass(): string
    {
        return match ($this->level) {
            'warning' => 'bg-amber-400/15 text-amber-200',
            'error'   => 'bg-rose-400/15 text-rose-200',
            default   => 'bg-sky-400/15 text-sky-200',
        };
    }
}

