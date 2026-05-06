@php
    $primaryItems = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'active' => request()->routeIs('dashboard'),
            'icon' => 'dashboard',
        ],
        [
            'label' => 'Reports',
            'route' => 'reports.index',
            'active' => request()->routeIs('reports.*'),
            'icon' => 'reports',
        ],
        [
            'label' => 'Alerts',
            'route' => 'alerts.index',
            'active' => request()->routeIs('alerts.*'),
            'icon' => 'bell',
        ],
        [
            'label' => 'IMAP Accounts',
            'route' => 'imap-accounts.index',
            'active' => request()->routeIs('imap-accounts.*'),
            'icon' => 'inbox',
        ],
        [
            'label' => 'Profile',
            'route' => 'profile.edit',
            'active' => request()->routeIs('profile.*'),
            'icon' => 'profile',
        ],
    ];

    $adminItems = [];

    if (config('app.auth_diagnostics_enabled')) {
        $adminItems[] = [
            'label' => 'Auth Logs',
            'route' => 'auth-diagnostics.index',
            'active' => request()->routeIs('auth-diagnostics.*'),
            'icon' => 'shield',
        ];
    }

    if (config('app.system_logs_ui_enabled')) {
        $adminItems[] = [
            'label' => 'System Logs',
            'route' => 'system-logs.index',
            'active' => request()->routeIs('system-logs.*'),
            'icon' => 'terminal',
        ];
    }

    $iconPaths = [
        'dashboard' => 'M3 12h18M12 3v18',
        'reports' => 'M4 6h16M4 12h16M4 18h10',
        'inbox' => 'M4 7h16v10H4z M4 13h5l2 3h2l2-3h5',
        'profile' => 'M15 19v-1a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v1 M12 7a4 4 0 1 0 0 0.01',
        'bell'    => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        'shield' => 'M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3z',
        'terminal' => 'M4 6h16v12H4z M8 10l2 2-2 2 M12 14h4',
    ];
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
                <p class="px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Navigation</p>
                <nav class="mt-2 space-y-1">
                    @foreach ($primaryItems as $item)
                        <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-sky-400/15 text-sky-200 ring-1 ring-inset ring-sky-300/30' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-sky-300/15 text-sky-200' : 'bg-white/5 text-slate-400' }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $iconPaths[$item['icon']] }}" />
                                </svg>
                            </span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                @can('view-admin-tools')
                    @if ($adminItems !== [])
                        <div class="mt-5 border-t border-white/10 pt-4">
                            <p class="px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Admin tools</p>
                            <nav class="mt-2 space-y-1">
                                @foreach ($adminItems as $item)
                                    <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-violet-400/20 text-violet-200 ring-1 ring-inset ring-violet-300/30' : 'text-violet-300/80 hover:bg-violet-400/10 hover:text-violet-200' }}">
                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-violet-300/15 text-violet-200' : 'bg-violet-500/10 text-violet-300/80' }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $iconPaths[$item['icon']] }}" />
                                            </svg>
                                        </span>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    @endif
                @endcan

                <div class="mt-5 border-t border-white/10 pt-4">
                    <form method="POST" action="{{ route('filters.domain.update') }}" class="mb-4">
                        @csrf
                        <label for="sidebar_domain_mobile" class="px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Domain filter</label>
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

                <div class="mt-3 px-1 text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('privacy-policy') }}" class="hover:text-slate-200">Privacy Policy</a>
                        <span aria-hidden="true">&middot;</span>
                        <a href="https://github.com/burnacid/dmarc-dashboard" target="_blank" rel="noreferrer noopener" class="hover:text-slate-200">GitHub</a>
                    </div>
                    <p class="mt-1">&copy; {{ now()->year }} Lenders-IT. All rights reserved.</p>
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

        <p class="px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Navigation</p>
        <nav class="mt-2 space-y-1">
            @foreach ($primaryItems as $item)
                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-sky-400/15 text-sky-200 ring-1 ring-inset ring-sky-300/30' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-sky-300/15 text-sky-200' : 'bg-white/5 text-slate-400' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $iconPaths[$item['icon']] }}" />
                        </svg>
                    </span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        @can('view-admin-tools')
            @if ($adminItems !== [])
                <div class="mt-6 border-t border-white/10 pt-4">
                    <p class="px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Admin tools</p>
                    <nav class="mt-2 space-y-1">
                        @foreach ($adminItems as $item)
                            <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-violet-400/20 text-violet-200 ring-1 ring-inset ring-violet-300/30' : 'text-violet-300/80 hover:bg-violet-400/10 hover:text-violet-200' }}">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-violet-300/15 text-violet-200' : 'bg-violet-500/10 text-violet-300/80' }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $iconPaths[$item['icon']] }}" />
                                    </svg>
                                </span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            @endif
        @endcan

        <div class="mt-6 border-t border-white/10 pt-4">
            <form method="POST" action="{{ route('filters.domain.update') }}">
                @csrf
                <label for="sidebar_domain" class="px-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Domain filter</label>
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

        <div class="mt-3 px-2 text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <a href="{{ route('privacy-policy') }}" class="hover:text-slate-200">Privacy Policy</a>
                <span aria-hidden="true">&middot;</span>
                <a href="https://github.com/burnacid/dmarc-dashboard" target="_blank" rel="noreferrer noopener" class="hover:text-slate-200">GitHub</a>
            </div>
            <p class="mt-1">&copy; {{ now()->year }} Lenders-IT. All rights reserved.</p>
        </div>
    </div>
</aside>

