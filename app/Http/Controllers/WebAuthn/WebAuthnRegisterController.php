<?php

namespace App\Http\Controllers\WebAuthn;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;

use function response;

class WebAuthnRegisterController
{
    /**
     * Returns a challenge to be verified by the user device.
     */
    public function options(AttestationRequest $request): Responsable
    {
        abort_if(! config('app.passkeys_enabled', true), 404);

        return $request
            ->fastRegistration()
//            ->userless()
//            ->allowDuplicates()
            ->toCreate();
    }

    /**
     * Registers a device for further WebAuthn authentication.
     */
    public function register(AttestedRequest $request): JsonResponse
    {
        abort_if(! config('app.passkeys_enabled', true), 404);

        $alias = trim((string) $request->input('alias', ''));

        return response()->json([
            'id' => $request->save($alias !== '' ? ['alias' => $alias] : []),
            'message' => __('Passkey saved successfully.'),
        ]);
    }
}
