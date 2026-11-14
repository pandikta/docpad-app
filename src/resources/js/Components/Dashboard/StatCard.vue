<script setup>
defineProps({
    icon: { type: String, required: true },
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
});
</script>

<template>
    <div
        class="flex items-center gap-4 min-w-[14rem] px-5 py-4 rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm"
    >
        <span
            class="flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-[#1f6f46] dark:text-emerald-400"
        >
            <i :class="icon" class="text-xl"></i>
        </span>
        <div>
            <div class="text-2xl font-bold text-slate-800 dark:text-surface-0 leading-tight">{{ value }}</div>
            <div class="text-sm text-slate-500 dark:text-surface-400">{{ label }}</div>
        </div>
    </div>
</template>
