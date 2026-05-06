<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DmarcNotificationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'email_to',
        'ntfy_url',
        'ntfy_token',
        'ntfy_ignore_certificate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ntfy_ignore_certificate' => 'boolean',
            'ntfy_token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alertRules(): BelongsToMany
    {
        return $this->belongsToMany(DmarcAlertRule::class, 'dmarc_alert_rule_notification_channel')
            ->withTimestamps();
    }
}

