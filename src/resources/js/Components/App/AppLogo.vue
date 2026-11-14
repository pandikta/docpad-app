<script setup>
defineProps({
    // Tampilkan teks nama aplikasi di samping ikon
    showName: { type: Boolean, default: true },
    // Class untuk teks nama aplikasi
    nameClass: { type: String, default: 'text-xl font-bold tracking-tight text-white' },
});

const appName = import.meta.env.VITE_APP_NAME || 'DocPad';
</script>

<template>
    <div class="flex items-center gap-3">
        <img
            src="/images/docpad-folder-icon.png"
            :alt="appName"
            class="h-10 w-auto object-contain shrink-0 select-none drop-shadow-sm"
        />
        <span v-if="showName" :class="nameClass">{{ appName }}</span>
    </div>
</template>
