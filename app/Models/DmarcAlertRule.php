<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DmarcAlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'metric',
        'domain',
        'threshold_multiplier',
        'min_absolute_increase',
        'min_messages',
        'window_minutes',
        'baseline_days',
        'cooldown_minutes',
        'notification_email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'threshold_multiplier' => 'float',
            'min_absolute_increase' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DmarcAlertEvent::class);
    }
}

