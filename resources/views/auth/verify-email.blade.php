<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-white">Verify your email</h1>
        <p class="mt-2 text-sm text-slate-400">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-100">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm text-slate-300 underline underline-offset-4 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-0">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
