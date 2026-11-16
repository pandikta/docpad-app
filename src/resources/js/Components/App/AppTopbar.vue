<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Popover from 'primevue/popover';
import { useToast } from 'primevue/usetoast';
import { useDarkMode } from '@/composables/useDarkMode';
import { useDocumentSearch } from '@/composables/useDocumentSearch';
import { useLocale } from '@/composables/useLocale';
import { formatDateTime, getInitials } from '@/utils/formatters';

defineEmits(['toggle-sidebar']);

const page = usePage();
const toast = useToast();
const { isDark, toggleDarkMode } = useDarkMode();
const { searchQuery, clearSearch } = useDocumentSearch();
const { locale, setLocale, t } = useLocale();

const userName = computed(() => page.props.auth.user?.name ?? '');
const userEmail = computed(() => page.props.auth.user?.email ?? '');
const initials = computed(() => getInitials(userName.value));

// Storage quota bar
const formatStorageBytes = (bytes) => {
    const value = Number(bytes ?? 0);
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    if (value < 1024 * 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)} MB`;
    return `${(value / (1024 * 1024 * 1024)).toFixed(2)} GB`;
};

const storageQuota = computed(() => page.props.storageQuota ?? null);
const storageUsageLabel = computed(() => {
    const used = formatStorageBytes(storageQuota.value?.used_bytes ?? 0);
    const limitMb = storageQuota.value?.limit_mb;
    const limit = limitMb === null || limitMb === undefined ? t('common.unlimited') : `${limitMb} MB`;
    return `${used} / ${limit}`;
});
const storagePercent = computed(() => {
    const limitBytes = Number(storageQuota.value?.limit_bytes ?? 0);
    if (!limitBytes || limitBytes <= 0) return 0;
    const usedBytes = Number(storageQuota.value?.used_bytes ?? 0);
    return Math.min(100, Math.max(0, Math.round((usedBytes / limitBytes) * 100)));
});
const storageBarColor = computed(() => {
    if (storagePercent.value >= 90) return 'bg-red-500';
    if (storagePercent.value >= 75) return 'bg-amber-500';
    return 'bg-[#1f6f46] dark:bg-emerald-500';
});

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

onMounted(() => {
    window.addEventListener('keydown', onShortcut);
    document.addEventListener('visibilitychange', onVisibilityChange);
    notifPollTimer = setInterval(fetchNotifications, 30000);
});
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onShortcut);
    document.removeEventListener('visibilitychange', onVisibilityChange);
    if (notifPollTimer) clearInterval(notifPollTimer);
});

// Notifications (auto-check tiap 30 detik saat tab aktif)
const notifMenu = ref(null);
const notifications = ref(page.props.notifications?.items ?? []);
const unreadCount = ref(Number(page.props.notifications?.unread_count ?? 0));
let notifPollTimer = null;

watch(
    () => page.props.notifications,
    (next) => {
        if (!next) return;
        notifications.value = next.items ?? [];
        unreadCount.value = Number(next.unread_count ?? 0);
    },
);

const fetchNotifications = async () => {
    if (typeof document !== 'undefined' && document.visibilityState === 'hidden') return;
    if (!page.props.auth?.user) return;

    try {
        const { data } = await window.axios.get(route('notifications.index'));
        const nextUnread = Number(data?.unread_count ?? 0);
        const nextItems = Array.isArray(data?.items) ? data.items : [];

        if (nextUnread > unreadCount.value && nextItems.length) {
            toast.add({
                severity: 'info',
                summary: t('topbar.notifications'),
                detail: formatNotifText(nextItems[0]),
                life: 4000,
            });
        }

        notifications.value = nextItems;
        unreadCount.value = nextUnread;
    } catch {
        // Abaikan error polling (mis. saat session habis atau offline)
    }
};

const onVisibilityChange = () => {
    if (document.visibilityState === 'visible') {
        fetchNotifications();
    }
};

const toggleNotifMenu = (event) => notifMenu.value?.toggle(event);

const formatNotifText = (item) => {
    const data = item?.data ?? {};
    const actor = data.actor_name ?? 'Someone';
    if (data.action === 'uploaded') {
        if (Number(data.document_count ?? 1) > 1) {
            return t('topbar.notif_uploaded_multi', { actor, count: data.document_count });
        }
        return t('topbar.notif_uploaded', { actor, name: data.document_name ?? '' });
    }
    if (data.action === 'shared') {
        return t('topbar.notif_shared', { actor, name: data.document_name ?? '' });
    }
    if (data.action === 'revoked') {
        return t('topbar.notif_revoked', { actor, name: data.document_name ?? '' });
    }
    if (data.action === 'password_changed_by_admin') {
        return t('topbar.notif_password_changed', { actor });
    }
    if (data.action === 'team_request_submitted') {
        return t('topbar.notif_team_request', { actor, name: data.document_name ?? '' });
    }
    return t('topbar.notif_created', { actor, name: data.document_name ?? '' });
};

const handleNotifClick = (item) => {
    if (!item?.id) return;
    notifMenu.value?.hide();

    const navigate = () => {
        if (item?.data?.action === 'team_request_submitted') {
            router.visit(route('settings.teams.index'));
            return;
        }
        if (!route().current('dashboard')) {
            router.visit(route('dashboard'));
        }
    };

    if (!item.read_at) {
        item.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        router.post(
            route('notifications.read', item.id),
            {},
            {
                preserveScroll: true,
                onFinish: navigate,
            },
        );
        return;
    }

    navigate();
};

const markAllAsRead = () => {
    if (!unreadCount.value) return;
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};

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
                :placeholder="t('topbar.search_placeholder')"
                fluid
                class="!rounded-xl !pr-9 !bg-slate-50 dark:!bg-surface-800 !border-slate-200 dark:!border-surface-700"
                @keydown.esc="clearSearch"
                @keydown.enter="onSearchEnter"
            />
            <button
                v-if="searchQuery"
                type="button"
                :title="t('topbar.clear_search')"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-surface-200 cursor-pointer"
                @click="clearSearch"
            >
                <i class="pi pi-times text-xs"></i>
            </button>
        </IconField>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            <!-- Storage Quota Bar -->
            <div
                v-if="storageQuota"
                v-tooltip.bottom="t('dashboard.storage_used')"
                class="hidden sm:flex flex-col justify-center min-w-[7.5rem] max-w-[10.5rem] gap-1 mr-1"
            >
                <span class="text-xs font-medium text-slate-600 dark:text-surface-200 leading-none truncate">
                    {{ storageUsageLabel }}
                </span>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-surface-700">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="storageBarColor"
                        :style="{ width: `${storagePercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- Dark mode -->
            <button
                type="button"
                :title="isDark ? t('topbar.switch_light') : t('topbar.switch_dark')"
                class="flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 dark:text-surface-300 hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer"
                @click="toggleDarkMode"
            >
                <i :class="isDark ? 'pi pi-sun text-amber-400' : 'pi pi-moon'"></i>
            </button>

            <!-- Notifications -->
            <button
                type="button"
                :title="t('topbar.notifications')"
                class="relative flex items-center justify-center w-9 h-9 rounded-lg text-slate-600 dark:text-surface-300 hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer"
                aria-haspopup="true"
                @click="toggleNotifMenu"
            >
                <i class="pi pi-bell"></i>
                <span
                    v-if="unreadCount > 0"
                    class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center leading-none"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </button>

            <Popover ref="notifMenu" class="w-80 sm:w-96">
                <div class="space-y-2 p-1">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-surface-800">
                        <span class="text-sm font-bold text-slate-800 dark:text-surface-0">
                            {{ t('topbar.notifications') }}
                        </span>
                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            class="text-xs font-semibold text-[#1f6f46] hover:underline dark:text-emerald-400 cursor-pointer"
                            @click="markAllAsRead"
                        >
                            {{ t('topbar.mark_all_read') }}
                        </button>
                    </div>

                    <div v-if="!notifications.length" class="py-6 text-center text-xs text-slate-400 dark:text-surface-500">
                        {{ t('topbar.no_notifications') }}
                    </div>

                    <ul v-else class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-surface-800">
                        <li v-for="item in notifications" :key="item.id">
                            <button
                                type="button"
                                class="w-full flex items-start gap-2.5 px-2 py-2.5 rounded-lg text-left transition-colors hover:bg-slate-50 dark:hover:bg-surface-800/70 cursor-pointer"
                                :class="!item.read_at ? 'bg-emerald-50/50 dark:bg-emerald-950/20' : ''"
                                @click="handleNotifClick(item)"
                            >
                                <span
                                    class="mt-1.5 w-2 h-2 rounded-full shrink-0"
                                    :class="!item.read_at ? 'bg-[#1f6f46] dark:bg-emerald-400' : 'bg-transparent'"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-slate-700 dark:text-surface-200 leading-snug">
                                        {{ formatNotifText(item) }}
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-400 dark:text-surface-500">
                                        {{ formatDateTime(item.created_at) }}
                                    </p>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>
            </Popover>

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
                        <p class="mb-2.5 text-xs font-semibold text-slate-600 dark:text-surface-300">{{ t('topbar.change_language') }}</p>
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
                            <span>{{ t('topbar.profile') }}</span>
                        </button>
                        <button
                            type="button"
                            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 cursor-pointer transition-colors"
                            @click="handleLogout"
                        >
                            <i class="pi pi-sign-out text-xs"></i>
                            <span>{{ t('topbar.logout') }}</span>
                        </button>
                    </div>
                </div>
            </Popover>
        </div>
    </header>
</template>
