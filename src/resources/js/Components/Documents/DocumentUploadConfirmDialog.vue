<script setup>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { formatFileSize } from '@/utils/formatters';

const props = defineProps({
    visible: { type: Boolean, default: false },
    files: { type: Array, default: () => [] },
    extensionMaxSizes: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'confirm']);

const close = () => emit('update:visible', false);

const getFileExtension = (fileName) => {
    const parts = String(fileName ?? '').split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
};

const getMaxSizeKb = (file) => {
    const ext = getFileExtension(file?.name);
    return Number(props.extensionMaxSizes?.[ext] ?? 1024);
};

const formatMaxKb = (kb) => {
    if (kb >= 1024) {
        const mb = kb / 1024;
        return `${Number.isInteger(mb) ? mb : mb.toFixed(1)} MB`;
    }
    return `${kb} KB`;
};

const isOversized = (file) => Number(file?.size ?? 0) > getMaxSizeKb(file) * 1024;

const onConfirm = () => {
    if (!props.files.length) return;
    emit('confirm', props.files);
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :draggable="false"
        :style="{ width: '30rem' }"
        :breakpoints="{ '640px': '92vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <i class="pi pi-cloud-upload text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">Confirm Upload</span>
            </div>
        </template>

        <div class="space-y-3">
            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                You are about to upload
                <span class="font-semibold text-slate-900 dark:text-surface-0">{{ files.length }}</span>
                {{ files.length === 1 ? 'document' : 'documents' }}.
                All uploaded documents will be set to
                <span class="font-semibold text-[#1f6f46] dark:text-emerald-400">Publish</span>
                and shared with your team.
            </p>

            <ul
                v-if="files.length"
                class="max-h-44 overflow-y-auto rounded-lg border border-slate-200 dark:border-surface-700 bg-slate-50 dark:bg-surface-900/50 divide-y divide-slate-200/70 dark:divide-surface-700/70 text-xs"
            >
                <li
                    v-for="(file, idx) in files"
                    :key="`${file.name}-${idx}`"
                    class="flex items-center justify-between gap-2 px-3 py-2"
                    :class="isOversized(file) ? 'text-amber-700 dark:text-amber-300 bg-amber-50/70 dark:bg-amber-950/30' : 'text-slate-700 dark:text-surface-200'"
                >
                    <div class="flex items-center gap-2 min-w-0">
                        <i
                            class="text-xs shrink-0"
                            :class="isOversized(file) ? 'pi pi-exclamation-triangle text-amber-500' : 'pi pi-file text-slate-400'"
                        ></i>
                        <span class="truncate font-medium">{{ file.name }}</span>
                    </div>
                    <span class="shrink-0 text-[11px] opacity-80">
                        {{ formatFileSize(file.size) }} / max {{ formatMaxKb(getMaxSizeKb(file)) }}
                    </span>
                </li>
            </ul>
        </div>

        <template #footer>
            <Button label="Cancel" severity="secondary" variant="outlined" :disabled="loading" @click="close" />
            <Button
                label="Upload & Publish"
                icon="pi pi-cloud-upload"
                :loading="loading"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                @click="onConfirm"
            />
        </template>
    </Dialog>
</template>
