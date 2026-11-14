<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import AppSidebar from '@/Components/App/AppSidebar.vue';
import AppTopbar from '@/Components/App/AppTopbar.vue';

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
const toast = useToast();
let removeListener = null;

onMounted(() => {
    removeListener = router.on('success', (event) => {
        const message = event.detail.page.props.flash?.success;
        if (message) {
            toast.add({ severity: 'success', summary: 'Success', detail: message, life: 3000 });
        }
    });
});

onBeforeUnmount(() => removeListener?.());
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
    </div>
</template>
