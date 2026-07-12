<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class DmarcRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'dmarc_report_id',
        'source_ip',
        'message_count',
        'disposition',
        'dkim',
        'dkim_domain',
        'dkim_selector',
        'dkim_aligned',
        'spf',
        'spf_domain',
        'spf_aligned',
        'header_from',
    ];

    protected function casts(): array
    {
        return [
            'spf_aligned' => 'boolean',
            'dkim_aligned' => 'boolean',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(DmarcReport::class, 'dmarc_report_id');
    }
}
