<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import AppSidebar from '@/Components/App/AppSidebar.vue';
import AppTopbar from '@/Components/App/AppTopbar.vue';
import SessionReloginDialog from '@/Components/App/SessionReloginDialog.vue';
import ForcePasswordChangeDialog from '@/Components/App/ForcePasswordChangeDialog.vue';

// Layout utama halaman setelah login: Sidebar + Topbar + konten halaman (slot).
const sidebarOpen = ref(false);
const sidebarMinimized = ref(
    typeof window !== 'undefined' && localStorage.getItem('sidebar_minimized') === '1',
);

const setSidebarMinimized = (value) => {
    sidebarMinimized.value = value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('sidebar_minimized', value ? '1' : '0');
    }
};

const toggleSidebarMinimize = () => setSidebarMinimized(!sidebarMinimized.value);

// Tampilkan flash message `success` dari backend sebagai toast (berlaku untuk semua halaman).
const page = usePage();
const toast = useToast();
const reloginVisible = ref(false);
const forcePasswordResetVisible = computed(() => Boolean(page.props.auth?.user?.is_reset));
const userEmail = computed(() => page.props.auth?.user?.email ?? '');
const sessionLifetimeMs = computed(() => Math.max(1, Number(page.props.sessionLifetime ?? 120)) * 60 * 1000);

let removeSuccessListener = null;
let removeFinishListener = null;
let removeInvalidListener = null;
let sessionTimer = null;

const resetSessionTimer = () => {
    if (sessionTimer) clearTimeout(sessionTimer);
    if (!userEmail.value || reloginVisible.value) return;

    sessionTimer = setTimeout(() => {
        reloginVisible.value = true;
    }, sessionLifetimeMs.value);
};

const onReloggedIn = () => {
    reloginVisible.value = false;
    resetSessionTimer();
    toast.add({ severity: 'success', summary: 'Session Restored', detail: 'Welcome back!', life: 2500 });
};

onMounted(() => {
    resetSessionTimer();

    removeSuccessListener = router.on('success', (event) => {
        const message = event.detail.page.props.flash?.success;
        if (message) {
            toast.add({ severity: 'success', summary: 'Success', detail: message, life: 3000 });
        }
        const warning = event.detail.page.props.flash?.warning;
        if (warning) {
            toast.add({ severity: 'warn', summary: 'Skipped Files', detail: warning, life: 6000 });
        }
    });

    removeFinishListener = router.on('finish', () => {
        resetSessionTimer();
    });

    removeInvalidListener = router.on('invalid', (event) => {
        const status = event.detail.response?.status;
        if (status === 419 || status === 401) {
            event.preventDefault();
            reloginVisible.value = true;
        }
    });
});

onBeforeUnmount(() => {
    if (sessionTimer) clearTimeout(sessionTimer);
    removeSuccessListener?.();
    removeFinishListener?.();
    removeInvalidListener?.();
});
</script>

<template>
    <div class="min-h-screen bg-[#f6f9f7] dark:bg-surface-950 transition-colors duration-200">
        <Toast position="top-center" />

        <AppSidebar
            :open="sidebarOpen"
            :minimized="sidebarMinimized"
            @close="sidebarOpen = false"
            @toggle-minimize="toggleSidebarMinimize"
            @set-minimized="setSidebarMinimized"
        />

        <div
            class="flex flex-col min-h-screen transition-[padding] duration-200"
            :class="sidebarMinimized ? 'lg:pl-20' : 'lg:pl-60'"
        >
            <AppTopbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <main class="flex-1 px-4 sm:px-8 py-6">
                <slot />
            </main>
        </div>

        <SessionReloginDialog
            :visible="reloginVisible"
            :email="userEmail"
            @relogged-in="onReloggedIn"
        />

        <ForcePasswordChangeDialog :visible="forcePasswordResetVisible && !reloginVisible" />
    </div>
</template>
