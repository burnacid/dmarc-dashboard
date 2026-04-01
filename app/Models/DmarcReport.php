<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class DmarcReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'imap_account_id',
        'external_report_id',
        'org_name',
        'email',
        'report_begin_at',
        'report_end_at',
        'policy_domain',
        'raw_xml',
    ];

    protected function casts(): array
    {
        return [
            'report_begin_at' => 'datetime',
            'report_end_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ImapAccount::class, 'imap_account_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(DmarcRecord::class);
    }
}
