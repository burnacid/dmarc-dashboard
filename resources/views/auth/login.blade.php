<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-white">Sign in</h1>
        <p class="mt-2 text-sm text-slate-400">Access your private DMARC monitoring workspace.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (config('app.passkeys_enabled', true))
    <div class="mb-6 rounded-3xl border border-sky-400/20 bg-sky-500/5 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">{{ __('Passkey sign in') }}</h2>
                <p class="mt-2 text-sm text-slate-300">
                    {{ __('Use Windows Hello, Touch ID, Face ID, or a security key instead of typing your password.') }}
                </p>
            </div>

            <button
                type="button"
                data-passkey-login
                data-email-input="#email"
                data-message-target="#passkey-login-message"
                class="inline-flex items-center justify-center rounded-2xl bg-sky-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0"
            >
                {{ __('Sign in with passkey') }}
            </button>
        </div>

        <p id="passkey-login-message" class="mt-4 text-sm text-slate-300" data-passkey-support-message>
            {{ __('Passkeys are supported on localhost or HTTPS in compatible browsers.') }}
        </p>
    </div>

    <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-white/10"></div>
        </div>
        <div class="relative flex justify-center">
            <span class="bg-slate-900 px-3 text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">{{ __('Or continue with password') }}</span>
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username webauthn" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-white/20 bg-slate-900 text-sky-400 focus:ring-sky-400" name="remember">
                <span class="ms-2 text-sm text-slate-300">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm text-slate-300 underline underline-offset-4 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            @if (config('app.registration_enabled', true) && Route::has('register'))
                <a class="ms-3 rounded-md text-sm text-slate-300 underline underline-offset-4 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0" href="{{ route('register') }}">
                    {{ __('Create an account') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
