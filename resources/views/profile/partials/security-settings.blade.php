@php
    $hasTwoFactor = $user->hasTwoFactorEnabled();
    $pendingTwoFactor = $user->twoFactorAuth->exists && $user->twoFactorAuth->isDisabled() ? $user->twoFactorAuth : null;
    $passkeys = $user->webAuthnCredentials->sortByDesc('created_at');
    $recoveryCodes = $hasTwoFactor ? $user->getRecoveryCodes() : collect();
@endphp

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-white">
            {{ __('Security & Multi-Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-slate-400">
            {{ __('Protect your account with one-time passwords and device-bound passkeys.') }}
        </p>
    </header>

    @if (session('two-factor-status'))
        <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
            {{ session('two-factor-status') }}
        </div>
    @endif

    @if (session('passkey-status'))
        <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">
            {{ session('passkey-status') }}
        </div>
    @endif

    @php
        $totpEnabled = config('app.totp_enabled', true);
        $passkeysEnabled = config('app.passkeys_enabled', true);
    @endphp

    @if (! $totpEnabled && ! $passkeysEnabled)
        <div class="rounded-3xl border border-dashed border-white/10 bg-slate-900/40 px-5 py-6 text-sm text-slate-400">
            {{ __('Passkeys and authenticator app sign-in are disabled by application configuration.') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-2">
        @if ($totpEnabled)
        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6 shadow-lg shadow-slate-950/20">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">{{ __('Authenticator app (TOTP)') }}</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ __('Use 6-digit codes from Google Authenticator, 2FAS, Authy, Microsoft Authenticator, or similar apps.') }}
                    </p>
                </div>

                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $hasTwoFactor ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/30' : 'bg-amber-500/15 text-amber-300 ring-1 ring-inset ring-amber-400/30' }}">
                    {{ $hasTwoFactor ? __('Enabled') : __('Not enabled') }}
                </span>
            </div>

            @if (! $hasTwoFactor && ! $pendingTwoFactor)
                <form method="POST" action="{{ route('security.two-factor.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="enable_two_factor_password" :value="__('Confirm your password to begin setup')" />
                        <x-text-input id="enable_two_factor_password" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                        <x-input-error class="mt-2" :messages="$errors->enableTwoFactor->get('password')" />
                    </div>

                    <x-primary-button>{{ __('Enable authenticator app') }}</x-primary-button>
                </form>
            @endif

            @if ($pendingTwoFactor)
                <div class="mt-6 space-y-5 rounded-3xl border border-sky-400/20 bg-sky-500/5 p-5">
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">{{ __('Step 1: scan the QR code') }}</h4>
                        <p class="mt-2 text-sm text-slate-300">
                            {{ __('Open your authenticator app and scan the QR code. If scanning is unavailable, enter the setup key manually.') }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-white p-4 text-slate-950 [&_svg]:mx-auto [&_svg]:h-52 [&_svg]:w-52">
                        {!! $pendingTwoFactor->toQr() !!}
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('Manual setup key') }}</p>
                        <p class="mt-2 break-all font-mono text-sm text-slate-100">{{ $pendingTwoFactor->toGroupedString() }}</p>
                    </div>

                    <form method="POST" action="{{ route('security.two-factor.confirm') }}" class="space-y-4">
                        @csrf

                        <div>
                            <x-input-label for="two_factor_otp" :value="__('Step 2: enter the 6-digit verification code (TOTP)')" />
                            <x-text-input id="two_factor_otp" name="otp" type="text" inputmode="numeric" class="mt-1 block w-full" autocomplete="one-time-code" autocapitalize="none" autocorrect="off" spellcheck="false" maxlength="12" />
                            <x-input-error class="mt-2" :messages="$errors->confirmTwoFactor->get('code')" />
                        </div>

                        <x-primary-button>{{ __('Confirm and activate') }}</x-primary-button>
                    </form>
                </div>
            @endif

            @if ($hasTwoFactor)
                <div class="mt-6 space-y-6">
                    <div x-data="{ showRecoveryCodes: false }" class="rounded-3xl border border-emerald-400/20 bg-emerald-500/5 p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-300">{{ __('Recovery codes') }}</h4>
                                <p class="mt-2 text-sm text-slate-300">
                                    {{ __('Store these one-time backup codes somewhere safe in case you lose access to your authenticator device.') }}
                                </p>
                                <p class="mt-2 text-xs text-slate-500">
                                    {{ __('Hidden by default for privacy. Click to reveal them when needed.') }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    x-on:click="showRecoveryCodes = ! showRecoveryCodes"
                                    x-bind:aria-expanded="showRecoveryCodes.toString()"
                                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white"
                                >
                                    <span x-text="showRecoveryCodes ? '{{ __('Hide recovery codes') }}' : '{{ __('Show recovery codes') }}'"></span>
                                    <svg class="h-4 w-4 transition" x-bind:class="showRecoveryCodes ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <form method="POST" action="{{ route('security.recovery-codes.store') }}">
                                    @csrf
                                    <x-secondary-button>{{ __('Regenerate codes') }}</x-secondary-button>
                                </form>
                            </div>
                        </div>

                        <div x-cloak x-show="showRecoveryCodes" x-transition class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($recoveryCodes as $code)
                                <div class="rounded-2xl border border-white/10 bg-slate-950/60 px-4 py-3 font-mono text-sm tracking-[0.2em] text-slate-100">
                                    <div>{{ data_get($code, 'code', $code) }}</div>

                                    @if (data_get($code, 'used_at'))
                                        <div class="mt-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-amber-300">
                                            {{ __('Used') }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <form method="POST" action="{{ route('security.two-factor.destroy') }}" class="space-y-4 rounded-3xl border border-rose-400/20 bg-rose-500/5 p-5">
                        @csrf
                        @method('DELETE')

                        <div>
                            <h4 class="text-sm font-semibold uppercase tracking-[0.2em] text-rose-300">{{ __('Disable authenticator app') }}</h4>
                            <p class="mt-2 text-sm text-slate-300">
                                {{ __('Disabling TOTP will remove your current secret and recovery codes.') }}
                            </p>
                        </div>

                        <div>
                            <x-input-label for="disable_two_factor_password" :value="__('Confirm your password')" />
                            <x-text-input id="disable_two_factor_password" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                            <x-input-error class="mt-2" :messages="$errors->disableTwoFactor->get('password')" />
                        </div>

                        <x-danger-button>{{ __('Disable TOTP') }}</x-danger-button>
                    </form>
                </div>
            @endif
        </div>
        @endif

        @if ($passkeysEnabled)
        <div class="rounded-3xl border border-white/10 bg-slate-950/60 p-6 shadow-lg shadow-slate-950/20">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-white">{{ __('Passkeys') }}</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ __('Use Windows Hello, Touch ID, Face ID, security keys, or platform passkeys for fast phishing-resistant sign-in.') }}
                    </p>
                </div>

                <span class="inline-flex items-center rounded-full bg-sky-500/15 px-3 py-1 text-xs font-semibold text-sky-300 ring-1 ring-inset ring-sky-400/30">
                    {{ trans_choice('{0} No passkeys|{1} :count passkey|[2,*] :count passkeys', $passkeys->count(), ['count' => $passkeys->count()]) }}
                </span>
            </div>

            <div class="mt-6 rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                    <div>
                        <x-input-label for="passkey_alias" :value="__('Passkey label (optional)')" />
                        <x-text-input id="passkey_alias" type="text" class="mt-1 block w-full" placeholder="{{ __('Office laptop, YubiKey, iPhone, …') }}" />
                        <p class="mt-2 text-xs text-slate-500">
                            {{ __('You can leave this empty and rename it later in the database if needed.') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        data-passkey-register
                        data-alias-input="#passkey_alias"
                        data-message-target="#passkey-register-message"
                        class="inline-flex items-center justify-center rounded-2xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0"
                    >
                        {{ __('Add passkey') }}
                    </button>
                </div>

                <p id="passkey-register-message" class="mt-4 text-sm text-slate-300" data-passkey-support-message>
                    {{ __('Passkeys work on localhost or HTTPS in supported browsers.') }}
                </p>
            </div>

            <div class="mt-6 space-y-4">
                @forelse ($passkeys as $credential)
                    <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-sm font-semibold text-white">{{ $credential->alias ?: __('Unnamed passkey') }}</h4>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $credential->isEnabled() ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-inset ring-emerald-400/30' : 'bg-rose-500/15 text-rose-300 ring-1 ring-inset ring-rose-400/30' }}">
                                        {{ $credential->isEnabled() ? __('Active') : __('Disabled') }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-400">{{ $credential->origin }}</p>
                                <dl class="grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                    <div>
                                        <dt class="font-semibold uppercase tracking-[0.2em]">{{ __('Credential ID') }}</dt>
                                        <dd class="mt-1 break-all text-slate-400">{{ $credential->id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-semibold uppercase tracking-[0.2em]">{{ __('Created') }}</dt>
                                        <dd class="mt-1 text-slate-400">{{ $credential->created_at?->diffForHumans() ?? __('Unknown') }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <form method="POST" action="{{ route('security.passkeys.destroy', $credential) }}" class="w-full space-y-3 lg:max-w-xs">
                                @csrf
                                @method('DELETE')

                                <div>
                                    <x-input-label :for="'delete_passkey_password_'.$credential->id" :value="__('Confirm your password to remove')" />
                                    <x-text-input :id="'delete_passkey_password_'.$credential->id" name="password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                                </div>

                                <x-danger-button>{{ __('Delete passkey') }}</x-danger-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-white/10 bg-slate-900/40 px-5 py-6 text-sm text-slate-400">
                        {{ __('No passkeys have been registered yet.') }}
                    </div>
                @endforelse

                <x-input-error class="mt-2" :messages="$errors->deletePasskey->get('password')" />
            </div>
        </div>
        @endif
    </div>
</section>

