<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';

const props = defineProps({
    status: { type: Number, default: 500 },
});

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

const errorCopy = computed(() => {
    switch (props.status) {
        case 400:
            return {
                title: 'Permintaan Tidak Valid',
                line1: 'Permintaan yang dikirim tidak dapat dipahami oleh server.',
                line2: 'Silakan muat ulang halaman dan coba lagi.',
            };
        case 401:
            return {
                title: 'Autentikasi Diperlukan',
                line1: 'Sesi Anda belum terautentikasi atau telah berakhir.',
                line2: 'Silakan login kembali untuk melanjutkan.',
            };
        case 404:
            return {
                title: 'Halaman Tidak Ditemukan',
                line1: 'Halaman atau dokumen yang Anda cari tidak ditemukan atau telah dipindahkan.',
                line2: 'Periksa kembali URL atau kembali ke Dashboard.',
            };
        case 405:
            return {
                title: 'Metode Tidak Diizinkan',
                line1: 'Metode akses yang digunakan untuk halaman ini tidak diperbolehkan.',
                line2: 'Silakan kembali ke Dashboard untuk melanjutkan.',
            };
        case 413:
            return {
                title: 'Ukuran File Terlalu Besar',
                line1: 'File yang Anda unggah melebihi batas maksimal yang diizinkan server.',
                line2: 'Silakan perkecil ukuran file atau unggah secara bertahap.',
            };
        case 503:
            return {
                title: 'Layanan Sedang Dalam Perbaikan',
                line1: 'Sistem sedang dalam pemeliharaan atau sementara tidak tersedia.',
                line2: 'Silakan coba lagi beberapa saat lagi.',
            };
        case 419:
            return {
                title: 'Sesi Telah Berakhir',
                line1: 'Halaman sudah kedaluwarsa karena terlalu lama tidak aktif.',
                line2: 'Silakan muat ulang halaman dan coba lagi.',
            };
        case 422:
            return {
                title: 'Data Tidak Valid',
                line1: 'Permintaan yang dikirim tidak dapat diproses karena data tidak valid.',
                line2: 'Periksa kembali input Anda lalu coba lagi.',
            };
        case 429:
            return {
                title: 'Terlalu Banyak Permintaan',
                line1: 'Anda melakukan terlalu banyak permintaan dalam waktu singkat.',
                line2: 'Silakan tunggu beberapa saat sebelum mencoba lagi.',
            };
        case 500:
            return {
                title: 'Terjadi Kesalahan pada Server',
                line1: 'Maaf, saat ini kami mengalami kendala teknis.',
                line2: 'Silakan coba lagi beberapa saat lagi.',
            };
        default:
            return {
                title: 'Terjadi Kesalahan',
                line1: 'Maaf, permintaan Anda tidak dapat diproses saat ini.',
                line2: 'Silakan coba lagi beberapa saat lagi.',
            };
    }
});

const reloadPage = () => {
    window.location.reload();
};
</script>

<template>
    <Head :title="`${status} ${errorCopy.title} — Docpad`" />

    <div class="relative flex min-h-screen flex-col justify-between overflow-hidden bg-slate-50/70 text-slate-800 dark:bg-surface-950 dark:text-surface-100">
        <!-- Soft corner waves -->
        <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-emerald-100/70 blur-2xl dark:bg-emerald-950/30"></div>
        <div class="pointer-events-none absolute -right-24 -bottom-20 h-80 w-80 rounded-full bg-emerald-100/70 blur-2xl dark:bg-emerald-950/30"></div>

        <!-- Top Navbar -->
        <header class="relative z-10 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-surface-800 dark:bg-surface-900/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6">
                <Link href="/dashboard" class="flex items-center gap-2.5">
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
            <!-- Illustration: Distressed Document + Server Rack + Alert Triangle -->
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

                <!-- Ground shadow line -->
                <rect x="44" y="185" width="232" height="3" rx="1.5" fill="#B7DFCA" />

                <!-- Tangled scribble above server -->
                <path
                    d="M188 46C182 36 196 28 205 34C214 40 202 52 194 46C186 40 198 22 212 28C222 32 218 48 208 48"
                    stroke="#2BA368"
                    stroke-width="2.5"
                    stroke-linecap="round"
                />

                <!-- Sparkles top right -->
                <path d="M248 64L258 54" stroke="#1F8A55" stroke-width="3.5" stroke-linecap="round" />
                <path d="M254 78L267 75" stroke="#1F8A55" stroke-width="3.5" stroke-linecap="round" />
                <path d="M236 55L240 44" stroke="#1F8A55" stroke-width="3.5" stroke-linecap="round" />

                <!-- Server Rack -->
                <rect x="174" y="64" width="60" height="28" rx="5" fill="#1F8A55" stroke="#14633C" stroke-width="2.5" />
                <rect x="182" y="75" width="20" height="5" rx="2.5" fill="#52B788" />
                <circle cx="216" cy="77.5" r="2.5" fill="#A7F3D0" />
                <circle cx="224" cy="77.5" r="2.5" fill="#A7F3D0" />

                <rect x="174" y="96" width="60" height="28" rx="5" fill="#1F8A55" stroke="#14633C" stroke-width="2.5" />
                <rect x="182" y="107" width="20" height="5" rx="2.5" fill="#52B788" />
                <circle cx="216" cy="109.5" r="2.5" fill="#A7F3D0" />
                <circle cx="224" cy="109.5" r="2.5" fill="#A7F3D0" />

                <rect x="174" y="128" width="60" height="28" rx="5" fill="#1F8A55" stroke="#14633C" stroke-width="2.5" />
                <rect x="182" y="139" width="20" height="5" rx="2.5" fill="#52B788" />

                <rect x="174" y="160" width="60" height="25" rx="5" fill="#14633C" />

                <!-- Distressed Document Character -->
                <!-- Left arm -->
                <path
                    d="M92 128C78 134 78 150 88 156"
                    stroke="#243B34"
                    stroke-width="3.5"
                    stroke-linecap="round"
                />
                <!-- Right arm touching server -->
                <path
                    d="M154 128L174 124"
                    stroke="#243B34"
                    stroke-width="3.5"
                    stroke-linecap="round"
                />
                <!-- Legs -->
                <path d="M112 174V184H105" stroke="#243B34" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M134 174V184H141" stroke="#243B34" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />

                <!-- Paper body -->
                <path
                    d="M92 78C92 72.5 96.5 68 102 68H136L154 86V166C154 171.5 149.5 176 144 176H102C96.5 176 92 171.5 92 166V78Z"
                    fill="#FFFFFF"
                    stroke="#9ECBB3"
                    stroke-width="3"
                />
                <!-- Folded corner -->
                <path d="M136 68V86H154" fill="#E6F5ED" stroke="#9ECBB3" stroke-width="2.5" stroke-linejoin="round" />
                <!-- >_< eyes & frown -->
                <path d="M107 110L115 114L107 118" stroke="#243B34" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M139 110L131 114L139 118" stroke="#243B34" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M115 134C119 128 127 128 131 134" stroke="#243B34" stroke-width="3" stroke-linecap="round" />

                <!-- Red Warning Triangle -->
                <path
                    d="M225 126L250 170C252 173.5 249.5 178 245.5 178H196.5C192.5 178 190 173.5 192 170L217 126C219 122.5 223 122.5 225 126Z"
                    fill="#EF4444"
                    stroke="#FFFFFF"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                />
                <rect x="219" y="142" width="4" height="16" rx="2" fill="#FFFFFF" />
                <circle cx="221" cy="166" r="2.5" fill="#FFFFFF" />
            </svg>

            <h1 class="mt-2 text-5xl font-extrabold tracking-tight text-[#0f3d28] dark:text-surface-0 sm:text-6xl">
                {{ status }}
            </h1>
            <h2 class="mt-1 text-2xl font-bold text-[#0f3d28] dark:text-surface-100 sm:text-3xl">
                {{ errorCopy.title }}
            </h2>

            <p class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-surface-300 sm:text-base">
                {{ errorCopy.line1 }}<br />
                {{ errorCopy.line2 }}
            </p>

            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <Button
                    label="Coba Lagi"
                    icon="pi pi-refresh"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-6 !py-3 shadow-sm"
                    @click="reloadPage"
                />
                <Link href="/dashboard">
                    <Button
                        label="Kembali ke Dashboard"
                        icon="pi pi-home"
                        severity="secondary"
                        variant="outlined"
                        class="!font-semibold !rounded-xl !px-6 !py-3"
                    />
                </Link>
            </div>
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
