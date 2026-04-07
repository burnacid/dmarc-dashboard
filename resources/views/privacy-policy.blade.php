<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Privacy Policy · {{ config('app.name', 'DMARC Dashboard') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
        <div class="flex min-h-screen flex-col bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.18),_transparent_35%),linear-gradient(180deg,_#020617_0%,_#0f172a_100%)]">
            <main class="mx-auto flex w-full max-w-4xl flex-1 flex-col px-4 py-10 sm:px-6 lg:px-8">
                <div class="mb-8 flex justify-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-slate-900/70 px-4 py-3 text-sm text-slate-200 shadow-xl shadow-slate-950/30 backdrop-blur transition hover:border-sky-400/40 hover:text-white">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-300 ring-1 ring-inset ring-sky-400/30">
                            <x-application-logo class="h-5 w-5 fill-current" />
                        </span>
                        <span>
                            <span class="block font-semibold">DMARC Dashboard</span>
                            <span class="block text-xs text-slate-400">Back to secure access</span>
                        </span>
                    </a>
                </div>

                <div class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 shadow-2xl shadow-slate-950/40 backdrop-blur sm:p-8">
                    <div class="mb-8 border-b border-white/10 pb-6">
                        <p class="text-sm uppercase tracking-[0.24em] text-sky-300">Legal</p>
                        <h1 class="mt-3 text-3xl font-semibold text-white">Privacy Policy</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">
                            This Privacy Policy explains how <strong>Stefan Lenders</strong>, operating as <strong>Lenders-IT</strong>,
                            handles personal data in connection with the DMARC Dashboard application.
                        </p>
                        <p class="mt-2 text-xs text-slate-400">
                            Effective date: {{ now()->format('F j, Y') }}
                        </p>
                    </div>

                    <div class="space-y-8 text-sm leading-7 text-slate-300 sm:text-base">
                        <section>
                            <h2 class="text-lg font-semibold text-white">1. Data we process</h2>
                            <p class="mt-2">
                                Depending on how you use the application, the service may process account information, login and security data,
                                IMAP mailbox connection details, DMARC aggregate report data, and technical logs generated to operate and secure the service.
                            </p>
                            <ul class="mt-3 list-disc space-y-2 pl-5 text-slate-300">
                                <li>User profile information such as name, email address, and account preferences.</li>
                                <li>Authentication and security data, including password hashes, two-factor settings, and passkey metadata.</li>
                                <li>IMAP account configuration, including encrypted mailbox credentials stored by the application.</li>
                                <li>DMARC report content imported from configured mailboxes, including sender domains, aggregate authentication results, and related metadata.</li>
                                <li>Operational logs used for troubleshooting, security monitoring, and service reliability.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">2. Why we process data</h2>
                            <p class="mt-2">
                                Data is processed only as needed to authenticate users, connect to mailboxes, import DMARC reports,
                                display analytics, secure accounts, and maintain the service.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">3. How mailbox and DMARC data is used</h2>
                            <p class="mt-2">
                                The application reads messages from configured IMAP folders to identify DMARC aggregate reports.
                                Relevant XML payloads are parsed and stored so the dashboard can present authentication and delivery insights.
                                Messages may be moved to configured processed or error folders as part of mailbox housekeeping.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">4. Security measures</h2>
                            <p class="mt-2">
                                Reasonable technical measures are used to protect stored data, including encrypted storage for sensitive credentials,
                                authenticated access controls, and optional multi-factor authentication features.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">5. Data retention</h2>
                            <p class="mt-2">
                                Data is retained only for as long as it is needed for service operation, reporting history, security,
                                backup, or legal compliance. Retention periods may vary depending on account settings and operational requirements.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">6. Sharing of data</h2>
                            <p class="mt-2">
                                Personal data is not sold. Data may be disclosed only when necessary to provide the service,
                                comply with legal obligations, investigate abuse, or protect the rights, property, and security of the operator or users.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">7. Your choices and rights</h2>
                            <p class="mt-2">
                                Subject to applicable law, you may request access to, correction of, or deletion of your personal data,
                                and you may request additional information about how your data is handled.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-lg font-semibold text-white">8. Policy updates</h2>
                            <p class="mt-2">
                                This policy may be updated from time to time to reflect operational, legal, or product changes.
                                The latest version will be published on this page.
                            </p>
                        </section>
                    </div>
                </div>
            </main>

            @include('layouts.footer')
        </div>
    </body>
</html>

