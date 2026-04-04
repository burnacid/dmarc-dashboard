<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

use function response;

class WebAuthnLoginController
{
    /**
     * Returns the challenge to assertion.
     */
    public function options(AssertionRequest $request): Responsable
    {
        abort_if(! config('app.passkeys_enabled', true), 404);

        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Log the user in.
     */
    public function login(AssertedRequest $request): JsonResponse
    {
        abort_if(! config('app.passkeys_enabled', true), 404);

        $user = $request->login();

        return response()->json(
            $user
                ? [
                    'redirect' => route('dashboard', absolute: false),
                    'user' => [
                        'id' => $user->getAuthIdentifier(),
                        'name' => $user->getAttribute('name'),
                        'email' => $user->getAttribute('email'),
                    ],
                ]
                : ['message' => __('Passkey sign in failed.')],
            $user ? 200 : 422,
        );
    }
}
