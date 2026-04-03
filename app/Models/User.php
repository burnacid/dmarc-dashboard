<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Laragear\TwoFactor\TwoFactorAuthentication;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;

#[Fillable(['name', 'email', 'password', 'report_retention_days', 'dashboard_range_presets'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements TwoFactorAuthenticatable, WebAuthnAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthentication, WebAuthnAuthentication;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dashboard_range_presets' => 'array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function allowedRangePresets(): array
    {
        return [
            '7d' => '7 days',
            '30d' => '30 days',
            '90d' => '90 days',
            '180d' => '6 months',
            '365d' => '12 months',
        ];
    }

    /**
     * @return list<string>
     */
    public function normalizedRangePresets(): array
    {
        $allowed = array_keys(self::allowedRangePresets());
        $selected = array_values(array_intersect(
            array_values(array_map('strval', (array) ($this->dashboard_range_presets ?? []))),
            $allowed
        ));

        if ($selected === []) {
            return ['7d', '30d', '90d', '180d', '365d'];
        }

        return $selected;
    }

    public function imapAccounts(): HasMany
    {
        return $this->hasMany(ImapAccount::class);
    }
}
