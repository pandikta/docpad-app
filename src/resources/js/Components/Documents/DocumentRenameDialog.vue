<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    visible: { type: Boolean, default: false },
    document: { type: Object, default: null },
    extensions: { type: Array, default: () => ['txt', 'md'] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'submit']);

const newName = ref('');

watch(
    () => props.document,
    (doc) => {
        newName.value = doc?.name ?? '';
    },
    { immediate: true },
);

// Validasi ekstensi file secara langsung saat mengetik
const validationError = computed(() => {
    const trimmed = newName.value.trim();
    if (!trimmed) return 'Document name is required.';

    const lastDot = trimmed.lastIndexOf('.');
    const baseName = lastDot > 0 ? trimmed.slice(0, lastDot).trim() : '';
    const ext = lastDot > 0 ? trimmed.slice(lastDot + 1).toLowerCase() : '';

    if (!baseName || !props.extensions.includes(ext)) {
        return `Document name must end with an allowed extension (.${props.extensions.join(', .')}).`;
    }

    return null;
});

const close = () => emit('update:visible', false);

const onSubmit = () => {
    if (validationError.value || !props.document) return;

    emit('submit', { document: props.document, name: newName.value.trim() });
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
                <i class="pi pi-pencil text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.rename_title') }}</span>
            </div>
        </template>

        <form class="space-y-2 pt-1" @submit.prevent="onSubmit">
            <label for="doc-new-name" class="block text-sm font-medium text-slate-700 dark:text-surface-200">
                {{ t('dialogs.new_name') }}
            </label>
            <InputText
                id="doc-new-name"
                v-model="newName"
                placeholder="example-notes.txt"
                autofocus
                required
                fluid
                :invalid="!!validationError"
                class="!rounded-xl"
            />
            <small v-if="validationError" class="text-red-500 text-xs flex items-center gap-1.5">
                <i class="pi pi-exclamation-circle"></i>
                <span>{{ validationError }}</span>
            </small>
            <small v-else class="text-slate-400 dark:text-surface-500 text-xs block">
                {{ t('dialogs.upload_allowed') }} .{{ extensions.join(', .') }}
            </small>
        </form>

        <template #footer>
            <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="close" />
            <Button
                :label="t('common.save')"
                icon="pi pi-check"
                :loading="loading"
                :disabled="!!validationError"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                @click="onSubmit"
            />
        </template>
    </Dialog>
</template>
