<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <!-- Shield background -->
    <defs>
        <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#0ea5e9;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:1" />
        </linearGradient>
    </defs>

    <!-- Shield -->
    <path d="M32 4L12 12V28C12 40 32 52 32 52C32 52 52 40 52 28V12L32 4Z" fill="url(#shieldGrad)" opacity="0.9"/>

    <!-- Shield border -->
    <path d="M32 4L12 12V28C12 40 32 52 32 52C32 52 52 40 52 28V12L32 4Z" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.4"/>

    <!-- Envelope inside shield -->
    <g opacity="0.95">
        <!-- Envelope body -->
        <rect x="20" y="24" width="24" height="16" rx="1" fill="white" opacity="0.2"/>

        <!-- Envelope flap top -->
        <path d="M20 24L32 32L44 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>

        <!-- Checkmark -->
        <path d="M26 32L30 36L38 28" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </g>
</svg>
