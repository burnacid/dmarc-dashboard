<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DmarcDnsRecordSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'record_type',
        'domain',
        'host',
        'selector',
        'records',
        'status',
        'error',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'records' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

