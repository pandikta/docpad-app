<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    visible: { type: Boolean, default: false },
    folders: { type: Array, default: () => [] },
    initialFolderId: { type: Number, default: null },
    count: { type: Number, default: 1 },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'submit']);

const selectedFolderId = ref(null);

const folderOptions = computed(() => [
    { id: null, name: 'Uncategorized (No Folder)' },
    ...props.folders,
]);

watch(
    () => props.visible,
    (isOpen) => {
        if (isOpen) {
            selectedFolderId.value = props.initialFolderId ?? null;
        }
    },
);

const close = () => emit('update:visible', false);
const submit = () => emit('submit', selectedFolderId.value);
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
                <i class="pi pi-folder text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.move_folder_title') }}</span>
            </div>
        </template>

        <div class="space-y-3 pt-1">
            <p class="text-sm text-slate-600 dark:text-surface-300">
                {{ t('dialogs.move_folder_desc', { count }) }}
            </p>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">{{ t('dialogs.folder') }}</label>
                <Select
                    v-model="selectedFolderId"
                    :options="folderOptions"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Folder"
                    fluid
                    class="!rounded-xl"
                />
            </div>
        </div>

        <template #footer>
            <Button :label="t('common.cancel')" severity="secondary" variant="outlined" :disabled="loading" @click="close" />
            <Button
                :label="t('table.move_folder')"
                icon="pi pi-check"
                :loading="loading"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                @click="submit"
            />
        </template>
    </Dialog>
</template>
