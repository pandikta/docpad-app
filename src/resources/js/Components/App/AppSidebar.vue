<script setup>
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/Components/App/AppLogo.vue';

defineProps({
    // Dipakai untuk membuka/menutup sidebar di layar kecil
    open: { type: Boolean, default: false },
});

defineEmits(['close']);

// Tambah menu baru cukup menambah item di sini.
const navItems = [
    { label: 'Documents', icon: 'pi pi-home', routeName: 'dashboard' },
    { label: 'Profile', icon: 'pi pi-user', routeName: 'profile.edit' },
];

const isActive = (routeName) => route().current(routeName);
</script>

<template>
    <!-- Overlay (mobile) -->
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-black/40 lg:hidden"
        @click="$emit('close')"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 w-60 flex flex-col overflow-hidden bg-gradient-to-b from-[#14573a] to-[#0f4a31] text-white transition-transform duration-200 lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Brand -->
        <div class="px-5 pt-6 pb-8">
            <AppLogo />
        </div>

        <!-- Navigation -->
        <nav class="px-3 space-y-1.5">
            <Link
                v-for="item in navItems"
                :key="item.routeName"
                :href="route(item.routeName)"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                :class="isActive(item.routeName)
                    ? 'bg-emerald-500/30 text-white'
                    : 'text-emerald-100/80 hover:bg-white/10 hover:text-white'"
            >
                <i :class="item.icon"></i>
                <span>{{ item.label }}</span>
            </Link>
        </nav>

        <!-- Decorative leaf -->
        <svg
            class="absolute inset-x-0 bottom-0 w-full h-48 pointer-events-none select-none opacity-60"
            viewBox="0 0 240 190"
            fill="none"
            preserveAspectRatio="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M0 190V110C40 80 100 85 140 105C180 125 215 110 240 90V190H0Z" fill="#1d6d46" />
            <!-- <path d="M60 110C50 80 62 50 90 38C96 66 86 96 60 110Z" fill="#1a6140" /> -->
        </svg>

        <!-- Logout -->
        <div class="relative mt-auto px-3 pb-6">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-emerald-100/90 hover:bg-white/10 hover:text-white transition-colors cursor-pointer"
            >
                <i class="pi pi-sign-out"></i>
                <span>Logout</span>
            </Link>
        </div>
    </aside>
</template>
