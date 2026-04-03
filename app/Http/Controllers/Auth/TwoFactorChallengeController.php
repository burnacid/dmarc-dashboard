<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): RedirectResponse|View
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            $request->session()->forget('auth.two-factor');

            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $state = $request->session()->get('auth.two-factor');
        $user = $this->pendingUser($request);

        if (! $state || ! $user) {
            $request->session()->forget('auth.two-factor');

            return redirect()->route('login');
        }

        if (! $user->validateTwoFactorCode($request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => __('The authentication code is invalid. You may also use a recovery code.'),
            ]);
        }

        $request->session()->forget('auth.two-factor');

        Auth::login($user, (bool) ($state['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    protected function pendingUser(Request $request): ?User
    {
        $state = $request->session()->get('auth.two-factor');

        if (! is_array($state) || empty($state['user_id'])) {
            return null;
        }

        $user = User::query()->find($state['user_id']);

        if (! $user?->hasTwoFactorEnabled()) {
            return null;
        }

        return $user;
    }
}

