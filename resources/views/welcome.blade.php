<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'DMARC Dashboard') }}</title>
        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.22),_transparent_35%),linear-gradient(180deg,_#020617_0%,_#0f172a_100%)]">
            <header class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-sky-300">DMARC Dashboard</p>
                    <p class="mt-2 text-sm text-slate-400">Laravel 13 starter for monitoring aggregate reports from one or many IMAP inboxes.</p>
                </div>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-500/20 transition hover:bg-sky-300">
                            Open dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="rounded-xl bg-sky-400 px-4 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-sky-500/20 transition hover:bg-sky-300">
                            Register
                        </a>
                    @endauth
                </div>
            </header>

            <main class="mx-auto flex max-w-7xl flex-col gap-10 px-4 py-10 sm:px-6 lg:flex-row lg:items-center lg:gap-16 lg:px-8 lg:py-20">
                <section class="flex-1">
                    <div class="inline-flex items-center rounded-full border border-sky-400/30 bg-sky-400/10 px-4 py-1 text-sm text-sky-200">
                        DMARC aggregate reports, parsed and visualized
                    </div>
                    <h1 class="mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Watch multiple IMAP inboxes and turn DMARC XML attachments into an actionable dashboard.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                        This first version gives you authenticated access, IMAP account management, scheduled polling, XML parsing for zipped or gzipped report attachments, and a clean Tailwind dashboard ready for future alert rules.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('imap-accounts.index') }}" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                                Manage IMAP accounts
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="rounded-2xl bg-sky-400 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-300">
                                Create your first workspace
                            </a>
                        @endauth
                        <a href="#features" class="rounded-2xl border border-white/10 px-5 py-3 text-sm font-medium text-slate-200 transition hover:bg-white/5">
                            See what is included
                        </a>
                    </div>
                </section>

                <section class="flex-1">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl shadow-slate-950/30">
                            <p class="text-sm font-medium text-sky-300">Included in v1</p>
                            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                                <li>• Login and registration</li>
                                <li>• Multi-account IMAP setup</li>
                                <li>• Dashboard metrics and report list</li>
                                <li>• Scheduler-friendly polling command</li>
                            </ul>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6">
                            <p class="text-sm font-medium text-emerald-300">Attachment support</p>
                            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                                <li>• Plain XML attachments</li>
                                <li>• ZIP archives</li>
                                <li>• GZ compressed payloads</li>
                                <li>• Per-record source IP storage</li>
                            </ul>
                        </div>
                        <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 sm:col-span-2">
                            <p class="text-sm font-medium text-amber-300">Roadmap ready</p>
                            <p class="mt-4 text-sm leading-7 text-slate-300">
                                Alert rules and notification emails are intentionally left for the next iteration, but the data model and dashboard structure are already prepared for it.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            <section id="features" class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <h2 class="text-lg font-semibold text-white">Secure account access</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-300">Breeze-based auth with a private dashboard for each user and encrypted IMAP password storage.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <h2 class="text-lg font-semibold text-white">Flexible IMAP polling</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-300">Configure host, port, encryption, folder, and search criteria per mailbox so one user can monitor multiple providers.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                        <h2 class="text-lg font-semibold text-white">Actionable report storage</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-300">Store report metadata, raw XML, and parsed rows so future alerting rules can build on the same dataset.</p>
                    </div>
                </div>
            </section>
        </div>
    </body>
</html>
