<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import AppLogo from '@/Components/App/AppLogo.vue';
import { useAuth } from '@/composables/useAuth';

const props = defineProps({
    // Dipakai untuk membuka/menutup sidebar di layar kecil
    open: { type: Boolean, default: false },
    // Mode minimize (icon rail) di layar desktop
    minimized: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'toggle-minimize', 'set-minimized']);

const toast = useToast();
const { isSuperAdmin, visibleMenus } = useAuth();

// Fallback jika tabel mst_menus belum di-seed
const fallbackMainMenus = [
    { code: 'menu.dashboard', name: 'Documents', icon: 'pi pi-home', route_name: 'dashboard' },
    { code: 'menu.profile', name: 'Profile', icon: 'pi pi-user', route_name: 'profile.edit' },
];

// Menu utama di sidebar diambil dinamis dari database (mst_menus)
const visibleNavItems = computed(() => {
    const fromDb = visibleMenus.value?.main ?? [];
    return fromDb.length ? fromDb : fallbackMainMenus;
});

// Panel sub-menu Settings (seperti contoh referensi gambar)
const settingsOpen = ref(false);
const settingsSearch = ref('');

// // Daftar grup & sub-menu statis sebelumnya (sekarang dibaca dinamis dari mst_menus)
// const settingsGroups = [
//     {
//         title: 'Account & Workspace',
//         items: [
//             { label: 'Profile Information', routeName: 'profile.edit', description: 'Update name and email address' },
//             { label: 'Document Workspace', routeName: 'dashboard', description: 'Back to document listing' },
//         ],
//     },
// ];

const filteredSettingsGroups = computed(() => {
    const q = settingsSearch.value.trim().toLowerCase();
    const items = visibleMenus.value?.settings ?? [];

    const groups = new Map();
    for (const item of items) {
        const matches =
            !q ||
            item.name.toLowerCase().includes(q) ||
            (item.description ?? '').toLowerCase().includes(q) ||
            (item.group ?? '').toLowerCase().includes(q);

        if (!matches) continue;

        const groupTitle = item.group || 'General';
        if (!groups.has(groupTitle)) groups.set(groupTitle, []);
        groups.get(groupTitle).push(item);
    }

    return Array.from(groups.entries()).map(([title, groupItems]) => ({
        title,
        items: groupItems,
    }));
});

const hasSettingsAccess = computed(
    () => isSuperAdmin.value || (visibleMenus.value?.settings?.length ?? 0) > 0,
);

const isActive = (routeName) => Boolean(routeName && route().current(routeName));
const isSettingsRouteActive = computed(() => route().current('settings.*'));

const toggleSettingsPanel = () => {
    settingsOpen.value = !settingsOpen.value;
    if (settingsOpen.value) {
        // Saat klik Settings, otomatis minimize sidebar menjadi icon rail seperti referensi gambar
        emit('set-minimized', true);
    }
};

const closeSettingsPanel = () => {
    settingsOpen.value = false;
};

const openSettingItem = (item) => {
    if (item.route_name && route().has(item.route_name)) {
        settingsOpen.value = false;
        emit('close');
        router.visit(route(item.route_name));
        return;
    }

    toast.add({
        severity: 'info',
        summary: item.name,
        detail: `Route "${item.route_name || '-'}" untuk menu "${item.name}" belum tersedia.`,
        life: 3000,
    });
};
</script>

<template>
    <!-- Overlay (mobile atau saat panel Settings terbuka) -->
    <div
        v-if="open || settingsOpen"
        class="fixed inset-0 z-30 bg-black/30 backdrop-blur-[1px] transition-opacity"
        :class="open ? '' : 'lg:bg-black/20'"
        @click="
            $emit('close');
            closeSettingsPanel();
        "
    ></div>

    <!-- Sidebar Utama (Bisa Expand w-60 atau Minimize jadi Icon Rail w-20) -->
    <aside
        class="fixed inset-y-0 left-0 z-40 flex flex-col overflow-hidden bg-gradient-to-b from-[#14573a] to-[#0f4a31] text-white transition-all duration-200 lg:translate-x-0"
        :class="[
            open ? 'translate-x-0' : '-translate-x-full',
            minimized ? 'w-60 lg:w-20' : 'w-60',
        ]"
    >
        <!-- Brand + Tombol Minimize/Expand -->
        <div
            class="flex items-center pt-6 pb-7"
            :class="minimized ? 'px-5 lg:px-0 lg:justify-center' : 'px-5 justify-between'"
        >
            <AppLogo :show-name="!minimized" />

            <button
                v-if="!minimized"
                v-tooltip.right="'Minimize sidebar'"
                type="button"
                class="hidden lg:flex items-center justify-center w-8 h-8 rounded-lg text-emerald-100/80 hover:bg-white/10 hover:text-white transition-colors cursor-pointer"
                @click="$emit('toggle-minimize')"
            >
                <i class="pi pi-angle-double-left text-sm"></i>
            </button>
        </div>

        <!-- Tombol Expand saat mode Minimized -->
        <div v-if="minimized" class="hidden lg:flex justify-center pb-4">
            <button
                v-tooltip.right="'Expand sidebar'"
                type="button"
                class="flex items-center justify-center w-10 h-10 rounded-xl text-emerald-100/80 hover:bg-white/10 hover:text-white transition-colors cursor-pointer"
                @click="
                    closeSettingsPanel();
                    $emit('toggle-minimize');
                "
            >
                <i class="pi pi-angle-double-right text-sm"></i>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="px-3 space-y-2">
            <Link
                v-for="item in visibleNavItems"
                :key="item.code"
                v-tooltip.right="minimized ? item.name : null"
                :href="item.route_name && route().has(item.route_name) ? route(item.route_name) : '#'"
                class="flex items-center rounded-xl text-sm font-medium transition-colors"
                :class="[
                    minimized
                        ? 'gap-3 px-3 py-2.5 lg:justify-center lg:gap-0 lg:p-0 lg:w-11 lg:h-11 lg:mx-auto lg:rounded-full'
                        : 'gap-3 px-3 py-2.5',
                    isActive(item.route_name) && !settingsOpen
                        ? 'bg-emerald-500/35 text-white shadow-sm ring-1 ring-white/20'
                        : 'text-emerald-100/80 hover:bg-white/10 hover:text-white',
                ]"
                @click="closeSettingsPanel"
            >
                <i :class="[item.icon || 'pi pi-circle', 'text-base leading-none']"></i>
                <span :class="minimized ? 'lg:hidden' : ''">{{ item.name }}</span>
            </Link>

            <!-- Menu Settings (Dikontrol Menu Authorization, Superadmin selalu bisa akses) -->
            <button
                v-if="hasSettingsAccess"
                v-tooltip.right="minimized ? 'Settings' : null"
                type="button"
                class="w-full flex items-center rounded-xl text-sm font-medium transition-colors cursor-pointer"
                :class="[
                    minimized
                        ? 'gap-3 px-3 py-2.5 lg:justify-center lg:gap-0 lg:p-0 lg:w-11 lg:h-11 lg:mx-auto lg:rounded-full'
                        : 'gap-3 px-3 py-2.5',
                    settingsOpen || isSettingsRouteActive
                        ? 'bg-white text-[#14573a] font-semibold shadow-sm'
                        : 'text-emerald-100/80 hover:bg-white/10 hover:text-white',
                ]"
                @click="toggleSettingsPanel"
            >
                <i class="pi pi-cog text-base leading-none"></i>
                <span :class="minimized ? 'lg:hidden' : ''">Settings</span>
                <span :class="minimized ? 'lg:hidden' : ''" class="ml-auto flex items-center">
                    <i
                        class="pi pi-chevron-right text-xs transition-transform"
                        :class="settingsOpen ? 'rotate-90' : ''"
                    ></i>
                </span>
            </button>
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
                v-tooltip.right="minimized ? 'Logout' : null"
                :href="route('logout')"
                method="post"
                as="button"
                class="w-full flex items-center rounded-xl text-sm font-medium text-emerald-100/90 hover:bg-white/10 hover:text-white transition-colors cursor-pointer"
                :class="
                    minimized
                        ? 'gap-3 px-3 py-2.5 lg:justify-center lg:gap-0 lg:p-0 lg:w-11 lg:h-11 lg:mx-auto lg:rounded-full'
                        : 'gap-3 px-3 py-2.5'
                "
            >
                <i class="pi pi-sign-out text-base leading-none"></i>
                <span :class="minimized ? 'lg:hidden' : ''">Logout</span>
            </Link>
        </div>
    </aside>

    <!-- Flyout Sub-Panel Settings (Persis seperti referensi gambar) -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-x-3"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 -translate-x-3"
    >
        <div
            v-if="hasSettingsAccess && settingsOpen"
            class="fixed inset-y-0 left-0 z-40 w-full sm:w-[36rem] lg:w-[42rem] bg-white dark:bg-surface-900 border-r border-slate-200 dark:border-surface-800 shadow-2xl overflow-y-auto px-6 sm:px-10 py-8 transition-all"
            :class="minimized ? 'lg:left-20' : 'lg:left-60'"
        >
            <!-- Header Panel -->
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <i class="pi pi-cog text-2xl text-[#1f6f46] dark:text-emerald-400"></i>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-surface-0">Settings</h2>
                </div>
                <button
                    type="button"
                    class="flex items-center justify-center w-9 h-9 rounded-full text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-surface-100 dark:hover:bg-surface-800 cursor-pointer"
                    @click="closeSettingsPanel"
                >
                    <i class="pi pi-times"></i>
                </button>
            </div>

            <!-- Search Bar di dalam Panel Settings -->
            <div class="mt-6">
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText
                        v-model="settingsSearch"
                        placeholder="  Find Menu Settings"
                        fluid
                        class="!rounded-full !py-2.5 !px-5 !bg-slate-50 dark:!bg-surface-800 !border-slate-200 dark:!border-surface-700"
                    />
                </IconField>
            </div>

            <!-- Daftar Grup & Sub-menu (2 kolom dengan garis vertikal di kiri seperti gambar) -->
            <div v-if="filteredSettingsGroups.length" class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-8">
                <div v-for="group in filteredSettingsGroups" :key="group.title" class="space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-surface-0">
                        {{ group.title }}
                    </h3>

                    <ul class="border-l-2 border-slate-200 dark:border-surface-700 pl-5 space-y-3.5">
                        <li v-for="sub in group.items" :key="sub.code">
                            <button
                                type="button"
                                class="group text-left w-full cursor-pointer"
                                @click="openSettingItem(sub)"
                            >
                                <span
                                    class="block text-sm font-medium text-slate-600 dark:text-surface-300 group-hover:text-[#1f6f46] dark:group-hover:text-emerald-400 transition-colors"
                                >
                                    {{ sub.name }}
                                </span>
                                <span
                                    v-if="sub.description"
                                    class="block text-xs text-slate-400 dark:text-surface-500 mt-0.5"
                                >
                                    {{ sub.description }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div v-else class="mt-12 text-center text-slate-400 dark:text-surface-500 text-sm">
                No setting menus matching "{{ settingsSearch.trim() }}".
            </div>
        </div>
    </Transition>
</template>
