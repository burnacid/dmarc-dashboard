<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class ImapAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'folder',
        'processed_folder',
        'error_folder',
        'search_criteria',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'last_polled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DmarcReport::class);
    }
}
