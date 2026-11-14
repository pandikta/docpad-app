<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import AppSidebar from '@/Components/App/AppSidebar.vue';
import AppTopbar from '@/Components/App/AppTopbar.vue';

// Layout utama halaman setelah login: Sidebar + Topbar + konten halaman (slot).
const sidebarOpen = ref(false);

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

        <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <div class="lg:pl-60 flex flex-col min-h-screen">
            <AppTopbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <main class="flex-1 px-4 sm:px-8 py-6">
                <slot />
            </main>
        </div>
    </div>
</template>
