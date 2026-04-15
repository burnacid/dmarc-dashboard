<?php

namespace App\Support\Auth;

use App\Models\AuthDiagnosticLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthDiagnostics
{
    public static function enabled(): bool
    {
        return (bool) config('app.auth_diagnostics_enabled', false);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function log(string $event, ?Request $request = null, array $context = [], string $level = 'info'): void
    {
        if (! self::enabled()) {
            return;
        }

        $base = self::baseContext($request);
        $merged = array_merge($base, $context);

        try {
            AuthDiagnosticLog::create([
                'event'                  => $event,
                'level'                  => $level,
                'user_id'                => $merged['user_id'] ?? null,
                'app_key_fingerprint'    => $merged['app_key_fingerprint'] ?? null,
                'ip_hash'                => $merged['ip_hash'] ?? null,
                'session_id_prefix'      => $merged['session_id_prefix'] ?? null,
                'remember_requested'     => $merged['remember_requested'] ?? $merged['remember_input'] ?? null,
                'remember_effective'     => $merged['remember_effective'] ?? null,
                'recaller_cookie_present' => $merged['recaller_cookie_present'] ?? null,
                'context'                => $merged,
            ]);
        } catch (Throwable) {
            // never crash a login flow due to diagnostics
        }
    }

    public static function appKeyFingerprint(): string
    {
        return substr(hash('sha256', (string) config('app.key')), 0, 16);
    }

    public static function emailHash(?string $email): ?string
    {
        $normalized = trim(strtolower((string) $email));

        if ($normalized === '') {
            return null;
        }

        return substr(hash('sha256', $normalized), 0, 16);
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseContext(?Request $request): array
    {
        if (! $request) {
            return [
                'app_key_fingerprint' => self::appKeyFingerprint(),
            ];
        }

        $session = $request->hasSession() ? $request->session() : null;
        $recallerName = Auth::guard('web')->getRecallerName();

        return [
            'guard' => 'web',
            'method' => $request->method(),
            'path' => $request->path(),
            'route' => optional($request->route())->getName(),
            'app_key_fingerprint' => self::appKeyFingerprint(),
            'session_started' => (bool) $session,
            'session_id_prefix' => $session ? substr((string) $session->getId(), 0, 12) : null,
            'session_has_two_factor_state' => $session ? $session->has('auth.two-factor') : false,
            'recaller_cookie_present' => $request->cookies->has($recallerName),
            'recaller_cookie_name' => $recallerName,
            'remember_input' => $request->boolean('remember'),
            'remember_header_present' => $request->headers->has('X-WebAuthn-Remember') || $request->headers->has('WebAuthn-Remember'),
            'ip_hash' => self::hash((string) $request->ip()),
            'user_agent_hash' => self::hash((string) $request->userAgent()),
            'input_keys' => array_values(array_filter(array_keys($request->all()), static fn (string $key): bool => ! in_array($key, ['password', 'code', 'otp', 'totp', 'verification_code', 'mfa_code', 'two_factor_code'], true))),
        ];
    }

    private static function hash(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return substr(hash('sha256', $value), 0, 16);
    }
}

