<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmarcAlertEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'dmarc_alert_rule_id',
        'triggered_at',
        'current_total_messages',
        'current_spf_fail_messages',
        'current_fail_rate',
        'baseline_total_messages',
        'baseline_spf_fail_messages',
        'baseline_fail_rate',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'current_fail_rate' => 'float',
            'baseline_fail_rate' => 'float',
            'context' => 'array',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(DmarcAlertRule::class, 'dmarc_alert_rule_id');
    }
}

