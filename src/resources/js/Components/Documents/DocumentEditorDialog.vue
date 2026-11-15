<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import Button from 'primevue/button';

const props = defineProps({
    // v-model:visible
    visible: { type: Boolean, default: false },
    // Dokumen yang sedang dibuka: { id, name, content }
    document: { type: Object, default: null },
    // Tampilkan loading di tombol Save
    saving: { type: Boolean, default: false },
    // Apakah user berhak mengedit dokumen
    canEdit: { type: Boolean, default: true },
});

const emit = defineEmits(['update:visible', 'save']);

// Salinan konten supaya perubahan tidak langsung menimpa data asli sebelum disimpan.
const content = ref('');

const isStoredFile = computed(() => Boolean(props.document?.is_stored_file));
const isPdf = computed(
    () =>
        props.document?.mime_type === 'application/pdf' ||
        props.document?.name?.toLowerCase().endsWith('.pdf'),
);

watch(
    () => props.document,
    (doc) => {
        content.value = doc?.content ?? '';
    },
    { immediate: true },
);

const close = () => emit('update:visible', false);

const save = () => emit('save', { ...props.document, content: content.value });
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        maximizable
        :draggable="false"
        :style="{ width: '52rem' }"
        :breakpoints="{ '1199px': '80vw', '575px': '95vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <i
                    class="text-xl"
                    :class="isPdf ? 'pi pi-file-pdf text-red-500' : 'pi pi-file-edit text-[#1f6f46] dark:text-emerald-400'"
                ></i>
                <span class="text-lg font-bold">{{ document?.name }}</span>
            </div>
        </template>

        <template v-if="isStoredFile">
            <iframe
                v-if="isPdf && document?.preview_url"
                :src="document.preview_url"
                class="w-full h-[70vh] rounded-xl border border-slate-200 dark:border-surface-700 bg-white"
            />
            <div
                v-else
                class="py-12 px-6 text-center rounded-xl border border-dashed border-slate-200 dark:border-surface-700 bg-slate-50 dark:bg-surface-900/50 space-y-3"
            >
                <i class="pi pi-file text-4xl text-slate-400"></i>
                <p class="text-sm text-slate-600 dark:text-surface-300">
                    Inline preview is not available for this file type.
                </p>
                <a
                    :href="route('documents.download', document.id)"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1f6f46] hover:bg-[#185a38] text-white text-sm font-semibold transition-colors"
                >
                    <i class="pi pi-download text-xs"></i>
                    <span>Download File</span>
                </a>
            </div>
        </template>

        <Editor v-else v-model="content" :readonly="!canEdit" editorStyle="height: 22rem" />

        <template #footer>
            <Button label="Close" severity="secondary" variant="outlined" @click="close" />
            <Button
                v-if="canEdit && !isStoredFile"
                label="Save"
                icon="pi pi-save"
                :loading="saving"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                @click="save"
            />
        </template>
    </Dialog>
</template>
