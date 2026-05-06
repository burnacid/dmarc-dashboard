<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    protected $fillable = [
        'channel',
        'level',
        'level_value',
        'message',
        'context',
        'extra',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'extra' => 'array',
            'logged_at' => 'datetime',
        ];
    }

    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function levelBadgeClass(): string
    {
        return match ($this->level) {
            'error', 'critical', 'alert', 'emergency' => 'bg-rose-400/15 text-rose-200',
            'warning' => 'bg-amber-400/15 text-amber-200',
            default => 'bg-sky-400/15 text-sky-200',
        };
    }
}

