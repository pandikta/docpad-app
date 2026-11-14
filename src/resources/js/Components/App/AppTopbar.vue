<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Menu from 'primevue/menu';
import { useDarkMode } from '@/composables/useDarkMode';
import { useDocumentSearch } from '@/composables/useDocumentSearch';
import { getInitials } from '@/utils/formatters';

defineEmits(['toggle-sidebar']);

const page = usePage();
const { isDark, toggleDarkMode } = useDarkMode();
const { searchQuery, clearSearch } = useDocumentSearch();

const userName = computed(() => page.props.auth.user?.name ?? '');
const initials = computed(() => getInitials(userName.value));

// Search (Ctrl/Cmd + K untuk fokus, Esc untuk reset)
const searchInput = ref(null);

const onShortcut = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchInput.value?.$el?.focus();
    }
};

const onSearchEnter = () => {
    if (!route().current('dashboard')) {
        router.get(route('dashboard'), { search: searchQuery.value || undefined }, { preserveState: true });
    }
};

onMounted(() => window.addEventListener('keydown', onShortcut));
onBeforeUnmount(() => window.removeEventListener('keydown', onShortcut));

// User menu
const userMenu = ref(null);
const userMenuItems = [
    { label: 'Profile', icon: 'pi pi-user', command: () => router.visit(route('profile.edit')) },
    { label: 'Logout', icon: 'pi pi-sign-out', command: () => router.post(route('logout')) },
];

const toggleUserMenu = (event) => userMenu.value.toggle(event);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex items-center gap-4 h-16 px-4 sm:px-8 bg-white/90 dark:bg-surface-900/90 backdrop-blur border-b border-slate-200 dark:border-surface-800"
    >
        <!-- Hamburger (mobile) -->
        <button
            type="button"
            class="lg:hidden flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 dark:text-surface-300 hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer"
            @click="$emit('toggle-sidebar')"
        >
            <i class="pi pi-bars"></i>
        </button>

        <!-- Search -->
        <IconField class="relative flex-1 max-w-xl">
            <InputIcon class="pi pi-search" />
            <InputText
                ref="searchInput"
                v-model="searchQuery"
                placeholder="Search by file name, content, or author...  (Ctrl + K)"
                fluid
                class="!rounded-xl !pr-9 !bg-slate-50 dark:!bg-surface-800 !border-slate-200 dark:!border-surface-700"
                @keydown.esc="clearSearch"
                @keydown.enter="onSearchEnter"
            />
            <button
                v-if="searchQuery"
                type="button"
                title="Clear search (Esc)"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-surface-200 cursor-pointer"
                @click="clearSearch"
            >
                <i class="pi pi-times text-xs"></i>
            </button>
        </IconField>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            <!-- Dark mode -->
            <button
                type="button"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                class="flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 dark:text-surface-300 hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer"
                @click="toggleDarkMode"
            >
                <i :class="isDark ? 'pi pi-sun text-amber-400' : 'pi pi-moon'"></i>
            </button>

            <!-- Notifications -->
            <button
                type="button"
                class="relative flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 dark:text-surface-300 hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer"
            >
                <i class="pi pi-bell"></i>
                <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-500"></span>
            </button>

            <!-- User -->
            <button
                type="button"
                class="flex items-center gap-1.5 cursor-pointer"
                aria-haspopup="true"
                @click="toggleUserMenu"
            >
                <span class="flex items-center justify-center w-9 h-9 rounded-full bg-[#1f6f46] text-white text-sm font-semibold">
                    {{ initials }}
                </span>
                <i class="pi pi-chevron-down text-xs text-slate-500 dark:text-surface-400"></i>
            </button>
            <Menu ref="userMenu" :model="userMenuItems" popup />
        </div>
    </header>
</template>
