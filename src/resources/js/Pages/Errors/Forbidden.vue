<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const userInitials = computed(() => {
    const name = String(user.value?.name ?? '').trim();
    if (!name) return 'DP';
    const parts = name.split(/\s+/);
    return parts.length >= 2
        ? `${parts[0][0]}${parts[1][0]}`.toUpperCase()
        : name.slice(0, 2).toUpperCase();
});
</script>

<template>
    <Head title="403 Forbidden — Docpad" />

    <div class="relative flex min-h-screen flex-col justify-between overflow-hidden bg-slate-50/70 text-slate-800 dark:bg-surface-950 dark:text-surface-100">
        <!-- Soft corner waves -->
        <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-emerald-100/70 blur-2xl dark:bg-emerald-950/30"></div>
        <div class="pointer-events-none absolute -right-24 -bottom-20 h-80 w-80 rounded-full bg-emerald-100/70 blur-2xl dark:bg-emerald-950/30"></div>

        <!-- Top Navbar -->
        <header class="relative z-10 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-surface-800 dark:bg-surface-900/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1f6f46] text-white">
                        <i class="pi pi-folder text-sm"></i>
                    </span>
                    <span class="text-lg font-bold tracking-tight text-[#0f3d28] dark:text-surface-0">Docpad</span>
                </Link>

                <div
                    v-if="user"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-[#1f6f46] text-xs font-bold text-white shadow-xs"
                    :title="user.name"
                >
                    {{ userInitials }}
                </div>
            </div>
        </header>

        <!-- Center Content -->
        <main class="relative z-10 mx-auto flex max-w-xl flex-col items-center px-4 py-12 text-center">
            <!-- Illustration: Sad Document + Emerald Padlock Shield -->
            <svg
                class="h-52 w-auto select-none sm:h-56"
                viewBox="0 0 320 230"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Soft background blob -->
                <path
                    d="M65 182C42 152 46 95 85 72C122 50 156 32 198 44C244 57 278 108 262 156C247 200 185 198 134 198C98 198 79 198 65 182Z"
                    fill="#E6F5ED"
                />

                <!-- Left leaves -->
                <path d="M56 184C42 172 40 154 50 144C62 152 64 170 56 184Z" fill="#7AD1A5" />
                <path d="M62 184C60 168 68 156 78 152C82 164 76 178 62 184Z" fill="#52B788" />

                <!-- Right leaves -->
                <path d="M244 184C240 162 248 140 264 134C270 154 260 174 244 184Z" fill="#52B788" />
                <path d="M250 184C256 168 270 160 282 164C276 178 262 186 250 184Z" fill="#7AD1A5" />

                <!-- Ground shadow line -->
                <rect x="44" y="183" width="232" height="3" rx="1.5" fill="#B7DFCA" />

                <!-- Shield -->
                <path
                    d="M196 36L244 54V104C244 142 222 170 196 184C170 170 148 142 148 104V54L196 36Z"
                    fill="#1F8A55"
                    stroke="#14633C"
                    stroke-width="3"
                    stroke-linejoin="round"
                />
                <path
                    d="M196 48L233 62V104C233 135 215 159 196 171C177 159 159 135 159 104V62L196 48Z"
                    fill="#2BA368"
                />

                <!-- Padlock inside shield -->
                <path
                    d="M181 96V86C181 77.7 187.7 71 196 71C204.3 71 211 77.7 211 86V96"
                    stroke="#FFFFFF"
                    stroke-width="6"
                    stroke-linecap="round"
                />
                <rect x="174" y="95" width="44" height="36" rx="7" fill="#FFFFFF" />
                <circle cx="196" cy="110" r="4.5" fill="#1F8A55" />
                <rect x="194" y="112" width="4" height="9" rx="2" fill="#1F8A55" />

                <!-- Sparkles top right -->
                <path d="M256 36L266 26" stroke="#1F8A55" stroke-width="3.5" stroke-linecap="round" />
                <path d="M262 50L275 47" stroke="#1F8A55" stroke-width="3.5" stroke-linecap="round" />
                <path d="M242 26L246 15" stroke="#1F8A55" stroke-width="3.5" stroke-linecap="round" />

                <!-- Sad Document Character -->
                <!-- Left arm -->
                <path
                    d="M90 128C78 134 78 150 90 156"
                    stroke="#243B34"
                    stroke-width="3.5"
                    stroke-linecap="round"
                />
                <!-- Right arm raised -->
                <path
                    d="M148 128C156 124 162 118 164 108"
                    stroke="#243B34"
                    stroke-width="3.5"
                    stroke-linecap="round"
                />
                <!-- Paper body -->
                <path
                    d="M90 82C90 76.5 94.5 72 100 72H134L150 88V172C150 177.5 145.5 182 140 182H100C94.5 182 90 177.5 90 172V82Z"
                    fill="#FFFFFF"
                    stroke="#9ECBB3"
                    stroke-width="3"
                />
                <!-- Folded corner -->
                <path d="M134 72V88H150" fill="#E6F5ED" stroke="#9ECBB3" stroke-width="2.5" stroke-linejoin="round" />
                <!-- Sad eyes & mouth -->
                <path d="M107 116C109 120 114 120 116 116" stroke="#243B34" stroke-width="2.8" stroke-linecap="round" />
                <path d="M126 116C128 120 133 120 135 116" stroke="#243B34" stroke-width="2.8" stroke-linecap="round" />
                <path d="M113 136C117 130 125 130 129 136" stroke="#243B34" stroke-width="3" stroke-linecap="round" />

                <!-- Red stop badge -->
                <circle cx="144" cy="172" r="14" fill="#EF4444" stroke="#FFFFFF" stroke-width="2.5" />
                <rect x="137" y="170" width="14" height="4" rx="2" fill="#FFFFFF" />
            </svg>

            <h1 class="mt-2 text-5xl font-extrabold tracking-tight text-[#0f3d28] dark:text-surface-0 sm:text-6xl">
                403
            </h1>
            <h2 class="mt-1 text-2xl font-bold text-[#0f3d28] dark:text-surface-100 sm:text-3xl">
                Forbidden
            </h2>

            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-surface-300 sm:text-base">
                Anda tidak memiliki akses untuk mengakses halaman ini.<br />
                Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
            </p>

            <Link :href="route('dashboard')" class="mt-7">
                <Button
                    label="Kembali ke Dashboard"
                    icon="pi pi-home"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-6 !py-3 shadow-sm"
                />
            </Link>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 border-t border-slate-200/80 bg-white/80 backdrop-blur dark:border-surface-800 dark:bg-surface-900/80">
            <div class="mx-auto max-w-6xl px-4 py-3.5 text-xs text-slate-500 dark:text-surface-400 sm:px-6">
                <span class="font-semibold text-[#1f6f46] dark:text-emerald-400">Docpad</span>
                <span class="mx-1.5">·</span>
                <span>Your Team's Document Hub.</span>
            </div>
        </footer>
    </div>
</template>
