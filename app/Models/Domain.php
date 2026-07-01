<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'organization_id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Register a domain name in the registry if it isn't already known.
     */
    public static function register(string $name): self
    {
        $normalized = strtolower(trim($name));

        return static::query()->firstOrCreate(['name' => $normalized]);
    }
}
