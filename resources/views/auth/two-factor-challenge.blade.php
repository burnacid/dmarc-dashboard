<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-white">{{ __('Two-factor verification') }}</h1>
        <p class="mt-2 text-sm text-slate-400">
            {{ __('Enter the code from your authenticator app or one of your recovery codes to finish signing in.') }}
        </p>
    </div>

    <div class="mb-6 rounded-3xl border border-sky-400/20 bg-sky-500/5 p-4 text-sm text-slate-300">
        <p class="font-medium text-white">{{ $user->email }}</p>
        <p class="mt-2">{{ __('MFA is enabled for this account. Your password was accepted, but a second step is required before access is granted.') }}</p>
    </div>

    <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-6">
        @csrf

        <div>
            <x-input-label for="code" :value="__('Authentication code or recovery code')" />
            <x-text-input
                id="code"
                class="mt-1 block w-full"
                type="text"
                name="code"
                :value="old('code')"
                required
                autofocus
                autocomplete="one-time-code"
                inputmode="numeric"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-4 text-sm text-slate-400">
            {{ __('Tip: recovery codes are accepted here too. They are one-time use and are best stored offline.') }}
        </div>

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="rounded-md text-sm text-slate-300 underline underline-offset-4 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0">
                {{ __('Back to sign in') }}
            </a>

            <x-primary-button>
                {{ __('Verify and continue') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

