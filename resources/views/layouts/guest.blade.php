<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DMARC Dashboard') }}</title>
        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
        <div class="flex min-h-screen flex-col bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.2),_transparent_35%),linear-gradient(180deg,_#020617_0%,_#0f172a_100%)]">
            <div class="flex flex-1 items-center justify-center px-4 py-10">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center justify-center gap-3">
                        <a href="{{ route('home') }}" class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-400/30">
                                <x-application-logo class="h-6 w-6 fill-current" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold tracking-wide text-white">DMARC Dashboard</p>
                                <p class="text-xs text-slate-400">Secure access</p>
                            </div>
                        </a>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-slate-900/70 px-6 py-6 shadow-2xl shadow-slate-950/40 backdrop-blur">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            @include('layouts.footer')
        </div>
    </body>
</html>
