<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useLocale } from '@/composables/useLocale';

const page = usePage();
const { locale, setLocale } = useLocale();

const enabled = computed(() => page.props.guestLanguageSwitcher !== false);
</script>

<template>
    <div
        v-if="enabled"
        class="flex items-center rounded-xl border border-slate-200 dark:border-surface-800 bg-white dark:bg-surface-900 p-0.5 shadow-sm"
        role="group"
        aria-label="Language switcher"
    >
        <button
            type="button"
            title="English"
            class="flex items-center gap-1 px-2 py-1.5 rounded-[9px] text-xs font-semibold transition-all cursor-pointer"
            :class="
                locale === 'en'
                    ? 'bg-[#eaf7f0] text-[#1f6f46] dark:bg-emerald-950/70 dark:text-emerald-300'
                    : 'text-slate-500 hover:text-slate-700 dark:text-surface-400 dark:hover:text-surface-200'
            "
            @click="setLocale('en')"
        >
            <span>EN</span>
        </button>

        <button
            type="button"
            title="Bahasa Indonesia"
            class="flex items-center gap-1 px-2 py-1.5 rounded-[9px] text-xs font-semibold transition-all cursor-pointer"
            :class="
                locale === 'id'
                    ? 'bg-[#eaf7f0] text-[#1f6f46] dark:bg-emerald-950/70 dark:text-emerald-300'
                    : 'text-slate-500 hover:text-slate-700 dark:text-surface-400 dark:hover:text-surface-200'
            "
            @click="setLocale('id')"
        >
            <span>ID</span>
        </button>
    </div>
</template>
