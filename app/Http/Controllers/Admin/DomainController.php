<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DomainController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'organization_id' => trim((string) $request->input('organization_id', '')),
        ];

        $domains = Domain::query()
            ->with('organization')
            ->when($filters['q'] !== '', fn ($query) => $query->where('name', 'like', '%'.$filters['q'].'%'))
            ->when($filters['organization_id'] === 'unassigned', fn ($query) => $query->whereNull('organization_id'))
            ->when(
                $filters['organization_id'] !== '' && $filters['organization_id'] !== 'unassigned',
                fn ($query) => $query->where('organization_id', $filters['organization_id'])
            )
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $organizations = Organization::query()->orderBy('name')->get();

        return view('admin.domains.index', compact('domains', 'filters', 'organizations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:domains,name'],
        ]);

        Domain::create(['name' => strtolower(trim($validated['name']))]);

        return to_route('admin.domains.index')->with('status', 'Domain created.');
    }

    public function update(Request $request, Domain $domain): RedirectResponse
    {
        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $domain->update(['organization_id' => $validated['organization_id'] ?? null]);

        return back()->with('status', 'Domain updated.');
    }

    public function destroy(Domain $domain): RedirectResponse
    {
        $domain->delete();

        return to_route('admin.domains.index')->with('status', 'Domain removed.');
    }
}
