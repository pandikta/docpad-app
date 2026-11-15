<script setup>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();
import { formatDateTime } from '@/utils/formatters';

defineProps({
    visible: { type: Boolean, default: false },
    // Dokumen yang dilihat log-nya: { id, name, logs: [{ id, user_name, action, created_at }] }
    document: { type: Object, default: null },
});

const emit = defineEmits(['update:visible']);

const close = () => emit('update:visible', false);
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :draggable="false"
        :style="{ width: '32rem' }"
        :breakpoints="{ '640px': '92vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex flex-col">
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-history text-lg text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.logs_title') }}</span>
                </div>
                <span v-if="document?.name" class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                    {{ document.name }}
                </span>
            </div>
        </template>

        <div class="py-2">
            <!-- Empty state -->
            <div
                v-if="!document?.logs?.length"
                class="py-8 text-center text-sm text-slate-500 dark:text-surface-400"
            >
                {{ t('dialogs.logs_empty') }}
            </div>

            <!-- Timeline list -->
            <ol v-else class="relative space-y-6 pl-2">
                <li
                    v-for="(log, index) in document.logs"
                    :key="log.id"
                    class="relative flex gap-4"
                >
                    <!-- Vertical connecting line -->
                    <span
                        v-if="index < document.logs.length - 1"
                        class="absolute left-[9px] top-6 -bottom-6 w-px bg-slate-200 dark:bg-surface-700"
                    ></span>

                    <!-- Timeline Dot (log terbaru di bagian atas diberi ring aktif) -->
                    <div class="relative z-10 flex items-center justify-center w-5 h-5 mt-0.5 shrink-0">
                        <span
                            v-if="index === 0"
                            class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-950"
                        >
                            <span class="w-2.5 h-2.5 rounded-full bg-[#1f6f46] dark:bg-emerald-400"></span>
                        </span>
                        <span
                            v-else
                            class="w-3 h-3 rounded-full bg-slate-300 dark:bg-surface-600"
                        ></span>
                    </div>

                    <!-- Log Content -->
                    <div class="flex-1 min-w-0">
                        <div class="text-xs text-slate-500 dark:text-surface-400">
                            {{ formatDateTime(log.created_at) }}
                        </div>
                        <div class="mt-0.5 text-sm text-slate-700 dark:text-surface-200">
                            <span class="font-semibold text-slate-900 dark:text-surface-0">{{ log.user_name }}</span>
                            {{ ' ' + log.action }}
                        </div>
                    </div>
                </li>
            </ol>
        </div>

        <template #footer>
            <Button :label="t('common.close')" severity="secondary" variant="outlined" @click="close" />
        </template>
    </Dialog>
</template>
