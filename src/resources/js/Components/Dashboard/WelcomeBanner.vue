<script setup>
defineProps({
    name: { type: String, default: '' },
    subtitle: { type: String, default: "Manage your team's documents easily." },
});
</script>

<template>
    <section
        class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#e3f5ea] via-[#d3eedf] to-[#c2e8d4] dark:from-emerald-950/60 dark:via-emerald-900/30 dark:to-emerald-900/20 border border-emerald-100 dark:border-emerald-900/40"
    >
        <div class="relative z-10 flex items-center gap-4 px-6 sm:px-8 py-8">
            <span class="hidden sm:block w-1 self-stretch rounded-full bg-[#1f6f46]"></span>
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#0f3d28] dark:text-surface-0">
                    Welcome back, {{ name }}!
                </h1>
                <p class="mt-1 text-sm sm:text-base text-[#35574a] dark:text-surface-300">
                    {{ subtitle }}
                </p>
            </div>
        </div>

        <!-- Illustration -->
        <svg
            class="absolute right-6 bottom-0 h-full max-h-36 w-auto pointer-events-none select-none hidden md:block"
            viewBox="0 0 220 140"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M40 140C70 90 130 80 220 100V140H40Z" fill="#b6e3cc" opacity="0.7" />
            <path
                d="M62 62C62 56 67 52 73 52H100L110 62H168C174 62 179 67 179 73V128H62V62Z"
                fill="#1f6f47"
            />
            <g transform="rotate(4 120 60)">
                <rect x="88" y="14" width="64" height="82" rx="6" fill="#ffffff" stroke="#cfe5d9" stroke-width="2" />
                <rect x="98" y="30" width="30" height="5" rx="2.5" fill="#b8ccc2" />
                <rect x="98" y="43" width="44" height="5" rx="2.5" fill="#b8ccc2" />
                <rect x="98" y="56" width="44" height="5" rx="2.5" fill="#b8ccc2" />
                <rect x="98" y="69" width="34" height="5" rx="2.5" fill="#b8ccc2" />
            </g>
            <path
                d="M60 84C60 76 66 72 74 72H108L118 82H170C176 82 181 87 180 94L174 130H66C62 130 59 127 59 123L60 84Z"
                fill="#4eb883"
                stroke="#1c6440"
                stroke-width="2"
            />
        </svg>
    </section>
</template>
