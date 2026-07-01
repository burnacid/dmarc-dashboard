<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationFilterController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'organization_id' => ['nullable', 'integer'],
        ]);

        $organizationId = $request->integer('organization_id') ?: null;

        if ($organizationId !== null && ! Organization::query()->whereKey($organizationId)->exists()) {
            $organizationId = null;
        }

        $request->session()->put('filters.organization', $organizationId);

        $selectedDomain = trim((string) $request->session()->get('filters.domain', ''));

        if ($selectedDomain !== '' && $organizationId !== null) {
            $domainBelongsToOrganization = Domain::query()
                ->where('name', $selectedDomain)
                ->where('organization_id', $organizationId)
                ->exists();

            if (! $domainBelongsToOrganization) {
                $request->session()->put('filters.domain', '');
            }
        }

        return back();
    }
}
