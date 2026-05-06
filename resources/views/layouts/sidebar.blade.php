@php
    $primaryItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'label' => 'Reports',
            'route' => 'reports.index',
            'active' => request()->routeIs('reports.*'),
        ],
        [
            'label' => 'IMAP Accounts',
            'route' => 'imap-accounts.index',
            'active' => request()->routeIs('imap-accounts.*'),
        ],
        [
            'label' => 'Profile',
            'route' => 'profile.edit',
            'active' => request()->routeIs('profile.*'),
        ],
    ];

    $adminItems = [];

    if (config('app.auth_diagnostics_enabled')) {
        $adminItems[] = [
            'label' => 'Auth Logs',
            'route' => 'auth-diagnostics.index',
            'active' => request()->routeIs('auth-diagnostics.*'),
        ];
    }

    if (config('app.system_logs_ui_enabled')) {
        $adminItems[] = [
            'label' => 'System Logs',
            'route' => 'system-logs.index',
            'active' => request()->routeIs('system-logs.*'),
        ];
    }
@endphp

<aside class="border-b border-white/10 bg-slate-950/80 backdrop-blur-xl md:fixed md:inset-y-0 md:left-0 md:z-30 md:w-72 md:border-b-0 md:border-r">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 md:hidden">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-400/30">
                <x-application-logo class="h-5 w-5 fill-current" />
            </div>
            <div>
                <p class="text-sm font-semibold tracking-wide text-white">DMARC Dashboard</p>
                <p class="text-xs text-slate-400">Workflow menu</p>
            </div>
        </a>

        <details>
            <summary class="flex cursor-pointer list-none items-center justify-center rounded-xl border border-white/10 bg-white/5 p-2 text-slate-300 marker:hidden">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </summary>

            <div class="absolute right-4 z-20 mt-3 w-80 rounded-3xl border border-white/10 bg-slate-950/95 p-4 shadow-2xl shadow-slate-950/50 backdrop-blur sm:right-6">
                <nav class="space-y-2">
                    @foreach ($primaryItems as $item)
                        <a href="{{ route($item['route']) }}" class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                @can('view-admin-tools')
                    @if ($adminItems !== [])
                        <div class="mt-5 border-t border-white/10 pt-4">
                            <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin tools</p>
                            <nav class="mt-2 space-y-2">
                                @foreach ($adminItems as $item)
                                    <a href="{{ route($item['route']) }}" class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/80 hover:bg-violet-400/10 hover:text-violet-200' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    @endif
                @endcan

                <div class="mt-5 border-t border-white/10 pt-4">
                    <form method="POST" action="{{ route('filters.domain.update') }}" class="mb-4">
                        @csrf
                        <label for="sidebar_domain_mobile" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Domain filter</label>
                        <select id="sidebar_domain_mobile" name="domain" onchange="this.form.submit()" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 [color-scheme:dark] focus:border-sky-400 focus:outline-none focus:ring-0">
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

    <div class="hidden md:flex md:h-full md:flex-col md:px-4 md:py-5">
        <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-3 rounded-2xl px-3 py-2 hover:bg-white/5">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-400/30">
                <x-application-logo class="h-6 w-6 fill-current" />
            </div>
            <div>
                <p class="text-sm font-semibold tracking-wide text-white">DMARC Dashboard</p>
                <p class="text-xs text-slate-400">Workflow menu</p>
            </div>
        </a>

        <nav class="space-y-2">
            @foreach ($primaryItems as $item)
                <a href="{{ route($item['route']) }}" class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        @can('view-admin-tools')
            @if ($adminItems !== [])
                <div class="mt-6 border-t border-white/10 pt-4">
                    <p class="px-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Admin tools</p>
                    <nav class="mt-2 space-y-2">
                        @foreach ($adminItems as $item)
                            <a href="{{ route($item['route']) }}" class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-violet-400/20 text-violet-200' : 'text-violet-300/80 hover:bg-violet-400/10 hover:text-violet-200' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif
        @endcan

        <div class="mt-6 border-t border-white/10 pt-4">
            <form method="POST" action="{{ route('filters.domain.update') }}">
                @csrf
                <label for="sidebar_domain" class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Domain filter</label>
                <select id="sidebar_domain" name="domain" onchange="this.form.submit()" class="mt-2 w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 [color-scheme:dark] focus:border-sky-400 focus:outline-none focus:ring-0">
                    <option value="">All domains</option>
                    @foreach (($globalDomainOptions ?? collect()) as $domain)
                        <option value="{{ $domain }}" @selected(($globalSelectedDomain ?? '') === $domain)>{{ $domain }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-auto rounded-2xl border border-white/10 bg-white/5 p-4">
            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
            <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>

            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800/80">
                    Log out
                </button>
            </form>
        </div>
    </div>
</aside>

