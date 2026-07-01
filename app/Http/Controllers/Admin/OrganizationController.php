<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        $organizations = Organization::query()
            ->withCount('domains')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        return view('admin.organizations.create', [
            'organization' => new Organization(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        Organization::create($request->validated());

        return to_route('admin.organizations.index')->with('status', 'Organization created.');
    }

    public function edit(Organization $organization): View
    {
        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        return to_route('admin.organizations.index')->with('status', 'Organization updated.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $organization->delete();

        return to_route('admin.organizations.index')->with('status', 'Organization deleted.');
    }
}
