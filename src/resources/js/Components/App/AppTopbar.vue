<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Popover from 'primevue/popover';
import { useDarkMode } from '@/composables/useDarkMode';
import { useDocumentSearch } from '@/composables/useDocumentSearch';
import { useLocale } from '@/composables/useLocale';
import { getInitials } from '@/utils/formatters';

defineEmits(['toggle-sidebar']);

const page = usePage();
const { isDark, toggleDarkMode } = useDarkMode();
const { searchQuery, clearSearch } = useDocumentSearch();
const { locale, setLocale } = useLocale();

const userName = computed(() => page.props.auth.user?.name ?? '');
const userEmail = computed(() => page.props.auth.user?.email ?? '');
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
const toggleUserMenu = (event) => userMenu.value.toggle(event);

const goToProfile = () => {
    userMenu.value?.hide();
    router.visit(route('profile.edit'));
};

const handleLogout = () => {
    userMenu.value?.hide();
    router.post(route('logout'));
};
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

            <Popover ref="userMenu" class="w-64">
                <div class="space-y-3 p-1">
                    <!-- User Info -->
                    <div class="border-b border-slate-100 pb-2.5 dark:border-surface-800">
                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-surface-0">{{ userName }}</p>
                        <p v-if="userEmail" class="truncate text-xs text-slate-500 dark:text-surface-400">{{ userEmail }}</p>
                    </div>

                    <!-- Change Language -->
                    <div class="border-b border-slate-100 pb-3 dark:border-surface-800">
                        <p class="mb-2.5 text-xs font-semibold text-slate-600 dark:text-surface-300">Change Language</p>
                        <div class="flex items-center gap-3">
                            <!-- English (UK Flag) -->
                            <button
                                type="button"
                                title="English"
                                class="relative flex h-6 w-9 items-center justify-center rounded-md border transition-all cursor-pointer"
                                :class="
                                    locale === 'en'
                                        ? 'border-[#1f6f46] ring-2 ring-[#1f6f46]/25'
                                        : 'border-slate-200 opacity-75 hover:opacity-100 dark:border-surface-700'
                                "
                                @click="setLocale('en')"
                            >
                                <svg viewBox="0 0 60 36" class="h-full w-full rounded-[5px] object-cover">
                                    <rect width="60" height="36" fill="#012169" />
                                    <path d="M0 0L60 36M60 0L0 36" stroke="#FFFFFF" stroke-width="6" />
                                    <path d="M0 0L60 36M60 0L0 36" stroke="#C8102E" stroke-width="2.5" />
                                    <path d="M30 0V36M0 18H60" stroke="#FFFFFF" stroke-width="10" />
                                    <path d="M30 0V36M0 18H60" stroke="#C8102E" stroke-width="6" />
                                </svg>
                                <span
                                    v-if="locale === 'en'"
                                    class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-[#1f6f46] text-white shadow-sm ring-2 ring-white dark:ring-surface-900"
                                >
                                    <i class="pi pi-check text-[8px] font-bold"></i>
                                </span>
                            </button>

                            <!-- Indonesia (ID Flag) -->
                            <button
                                type="button"
                                title="Bahasa Indonesia"
                                class="relative flex h-6 w-9 items-center justify-center rounded-md border transition-all cursor-pointer"
                                :class="
                                    locale === 'id'
                                        ? 'border-[#1f6f46] ring-2 ring-[#1f6f46]/25'
                                        : 'border-slate-200 opacity-75 hover:opacity-100 dark:border-surface-700'
                                "
                                @click="setLocale('id')"
                            >
                                <svg viewBox="0 0 60 36" class="h-full w-full rounded-[5px] object-cover">
                                    <rect width="60" height="18" fill="#DC2626" />
                                    <rect y="18" width="60" height="18" fill="#FFFFFF" />
                                </svg>
                                <span
                                    v-if="locale === 'id'"
                                    class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-[#1f6f46] text-white shadow-sm ring-2 ring-white dark:ring-surface-900"
                                >
                                    <i class="pi pi-check text-[8px] font-bold"></i>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Profile & Logout -->
                    <div class="space-y-1">
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-surface-200 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            @click="goToProfile"
                        >
                            <i class="pi pi-user text-xs"></i>
                            <span>Profile</span>
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 cursor-pointer transition-colors"
                            @click="handleLogout"
                        >
                            <i class="pi pi-sign-out text-xs"></i>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </Popover>
        </div>
    </header>
</template>
