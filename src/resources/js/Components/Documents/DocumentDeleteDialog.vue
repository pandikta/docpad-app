<script setup>
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    visible: { type: Boolean, default: false },
    document: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'confirm']);

const close = () => emit('update:visible', false);

const onConfirm = () => {
    if (!props.document) return;
    emit('confirm', props.document);
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :draggable="false"
        :style="{ width: '26rem' }"
        :breakpoints="{ '640px': '92vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <i class="pi pi-exclamation-triangle text-red-500"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.delete_title') }}</span>
            </div>
        </template>

        <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
            {{ t('dialogs.delete_confirm', { name: document?.name ?? '' }) }}
        </p>

        <template #footer>
            <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="close" />
            <Button
                :label="t('common.delete')"
                icon="pi pi-trash"
                severity="danger"
                :loading="loading"
                @click="onConfirm"
            />
        </template>
    </Dialog>
</template>
