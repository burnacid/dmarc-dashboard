<nav class="border-b border-white/10 bg-slate-950/70 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-400/30">
                    <x-application-logo class="h-6 w-6 fill-current" />
                </div>
                <div>
                    <p class="text-sm font-semibold tracking-wide text-white">DMARC Dashboard</p>
                    <p class="text-xs text-slate-400">IMAP aggregate monitoring</p>
                </div>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ route('reports.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('reports.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    Reports
                </a>
                <a href="{{ route('imap-accounts.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('imap-accounts.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    IMAP Accounts
                </a>
                <a href="{{ route('profile.edit') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('profile.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    Profile
                </a>
                @can('view-admin-tools')
                    @if (config('app.auth_diagnostics_enabled'))
                    <a href="{{ route('auth-diagnostics.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('auth-diagnostics.*') ? 'bg-amber-400/20 text-amber-200' : 'text-amber-300/70 hover:bg-amber-400/10 hover:text-amber-200' }}">
                        Auth Logs
                    </a>
                    @endif
                    @if (config('app.system_logs_ui_enabled'))
                    <a href="{{ route('system-logs.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('system-logs.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70 hover:bg-violet-400/10 hover:text-violet-200' }}">
                        System Logs
                    </a>
                    @endif
                @endcan
                @can('manage-users')
                <a href="{{ route('admin.users.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70 hover:bg-violet-400/10 hover:text-violet-200' }}">
                    Users
                </a>
                @endcan
                @can('manage-roles')
                <a href="{{ route('admin.roles.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.roles.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70 hover:bg-violet-400/10 hover:text-violet-200' }}">
                    Roles
                </a>
                @endcan
                @can('manage-organizations')
                <a href="{{ route('admin.organizations.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.organizations.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70 hover:bg-violet-400/10 hover:text-violet-200' }}">
                    Organizations
                </a>
                @endcan
                @can('manage-domains')
                <a href="{{ route('admin.domains.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.domains.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70 hover:bg-violet-400/10 hover:text-violet-200' }}">
                    Domains
                </a>
                @endcan
            </div>
        </div>

        <div class="hidden items-center gap-3 md:flex">
            @if (($globalOrganizationOptions ?? collect())->isNotEmpty())
                <form method="POST" action="{{ route('filters.organization.update') }}">
                    @csrf
                    <label for="nav_org" class="sr-only">Organization filter</label>
                    <select id="nav_org" name="organization_id" onchange="this.form.submit()" class="w-48 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 [color-scheme:dark] focus:border-sky-400 focus:outline-none focus:ring-0">
                        <option value="">All organizations</option>
                        @foreach (($globalOrganizationOptions ?? collect()) as $organization)
                            <option value="{{ $organization->id }}" @selected(($globalSelectedOrganization?->id ?? null) === $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            <form method="POST" action="{{ route('filters.domain.update') }}">
                @csrf
                <label for="nav_domain" class="sr-only">Domain filter</label>
                <select id="nav_domain" name="domain" onchange="this.form.submit()" class="w-52 rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 [color-scheme:dark] focus:border-sky-400 focus:outline-none focus:ring-0">
                    <option value="">All domains</option>
                    @foreach (($globalDomainOptions ?? collect()) as $domain)
                        <option value="{{ $domain }}" @selected(($globalSelectedDomain ?? '') === $domain)>{{ $domain }}</option>
                    @endforeach
                </select>
            </form>

            <div class="text-right">
                <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white">
                    Log out
                </button>
            </form>
        </div>

        <details class="md:hidden">
            <summary class="flex cursor-pointer list-none items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-slate-300 marker:hidden">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </summary>

            <div class="absolute right-4 z-10 mt-3 w-72 rounded-3xl border border-white/10 bg-slate-950/95 p-4 shadow-2xl shadow-slate-950/50 backdrop-blur sm:right-6">
                <div class="flex flex-col gap-2">
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Dashboard</a>
                    <a href="{{ route('reports.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Reports</a>
                    <a href="{{ route('imap-accounts.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('imap-accounts.*') ? 'bg-white/10 text-white' : 'text-slate-300' }}">IMAP Accounts</a>
                    <a href="{{ route('profile.edit') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('profile.*') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Profile</a>
                    @can('view-admin-tools')
                        @if (config('app.auth_diagnostics_enabled'))
                        <a href="{{ route('auth-diagnostics.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('auth-diagnostics.*') ? 'bg-amber-400/20 text-amber-200' : 'text-amber-300/70' }}">Auth Logs</a>
                        @endif
                        @if (config('app.system_logs_ui_enabled'))
                        <a href="{{ route('system-logs.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('system-logs.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70' }}">System Logs</a>
                        @endif
                    @endcan
                    @can('manage-users')
                    <a href="{{ route('admin.users.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70' }}">Users</a>
                    @endcan
                    @can('manage-roles')
                    <a href="{{ route('admin.roles.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.roles.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70' }}">Roles</a>
                    @endcan
                    @can('manage-organizations')
                    <a href="{{ route('admin.organizations.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.organizations.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70' }}">Organizations</a>
                    @endcan
                    @can('manage-domains')
                    <a href="{{ route('admin.domains.index') }}" class="rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.domains.*') ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/70' }}">Domains</a>
                    @endcan
                </div>

                <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                    @if (($globalOrganizationOptions ?? collect())->isNotEmpty())
                        <form method="POST" action="{{ route('filters.organization.update') }}" class="mb-4">
                            @csrf
                            <label for="nav_org_mobile" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Organization filter</label>
                            <select id="nav_org_mobile" name="organization_id" onchange="this.form.submit()" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 [color-scheme:dark] focus:border-sky-400 focus:outline-none focus:ring-0">
                                <option value="">All organizations</option>
                                @foreach (($globalOrganizationOptions ?? collect()) as $organization)
                                    <option value="{{ $organization->id }}" @selected(($globalSelectedOrganization?->id ?? null) === $organization->id)>{{ $organization->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('filters.domain.update') }}" class="mb-4">
                        @csrf
                        <label for="nav_domain_mobile" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Domain filter</label>
                        <select id="nav_domain_mobile" name="domain" onchange="this.form.submit()" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 [color-scheme:dark] focus:border-sky-400 focus:outline-none focus:ring-0">
                            <option value="">All domains</option>
                            @foreach (($globalDomainOptions ?? collect()) as $domain)
                                <option value="{{ $domain }}" @selected(($globalSelectedDomain ?? '') === $domain)>{{ $domain }}</option>
                            @endforeach
                        </select>
                    </form>

                    <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>

                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2 text-sm font-medium text-slate-200">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </details>
    </div>
</nav>
