<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import Dialog from 'primevue/dialog';
import Editor from 'primevue/editor';
import Button from 'primevue/button';
import { renderAsync } from 'docx-preview';

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
const isDocx = computed(() => Boolean(props.document?.name?.toLowerCase().endsWith('.docx')));

const docxContainer = ref(null);
const docxLoading = ref(false);
const docxError = ref('');

const loadDocxPreview = async () => {
    if (!props.visible || !isDocx.value || !props.document?.preview_url) return;

    docxLoading.value = true;
    docxError.value = '';

    await nextTick();
    if (docxContainer.value) {
        docxContainer.value.innerHTML = '';
    }

    try {
        const { data } = await window.axios.get(props.document.preview_url, {
            responseType: 'arraybuffer',
        });
        if (docxContainer.value) {
            await renderAsync(data, docxContainer.value, undefined, {
                inWrapper: true,
                ignoreWidth: false,
            });
        }
    } catch {
        docxError.value = 'Failed to render .docx preview. Please download the file instead.';
    } finally {
        docxLoading.value = false;
    }
};

watch(
    () => [props.visible, props.document?.id],
    () => {
        content.value = props.document?.content ?? '';
        loadDocxPreview();
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
                    :class="
                        isPdf
                            ? 'pi pi-file-pdf text-red-500'
                            : isDocx
                              ? 'pi pi-file-word text-blue-500'
                              : 'pi pi-file-edit text-[#1f6f46] dark:text-emerald-400'
                    "
                ></i>
                <span class="text-lg font-bold">{{ document?.name }}</span>
            </div>
        </template>

        <template v-if="isStoredFile">
            <div v-if="isDocx" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-surface-800/70 border border-slate-200/80 dark:border-surface-700 text-xs">
                    <span class="text-slate-600 dark:text-surface-300">
                        Read-only Word (.docx) preview
                    </span>
                    <a
                        :href="route('documents.download', document.id)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#1f6f46] hover:bg-[#185a38] text-white font-medium transition-colors"
                    >
                        <i class="pi pi-download text-[11px]"></i>
                        <span>Download</span>
                    </a>
                </div>

                <div v-if="docxLoading" class="py-16 text-center text-slate-500 dark:text-surface-400 space-y-2">
                    <i class="pi pi-spin pi-spinner text-2xl text-[#1f6f46]"></i>
                    <p class="text-sm">Rendering document preview...</p>
                </div>

                <div
                    v-else-if="docxError"
                    class="py-12 px-6 text-center rounded-xl border border-dashed border-slate-200 dark:border-surface-700 bg-slate-50 dark:bg-surface-900/50 space-y-3"
                >
                    <i class="pi pi-exclamation-triangle text-3xl text-amber-500"></i>
                    <p class="text-sm text-slate-600 dark:text-surface-300">{{ docxError }}</p>
                </div>

                <div
                    v-show="!docxLoading && !docxError"
                    ref="docxContainer"
                    class="max-h-[65vh] overflow-auto rounded-xl border border-slate-200 dark:border-surface-700 bg-slate-100 dark:bg-surface-900 p-4"
                />
            </div>

            <div v-else-if="isPdf && document?.preview_url" class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-surface-800/70 border border-slate-200/80 dark:border-surface-700 text-xs">
                    <span class="text-slate-600 dark:text-surface-300">
                        Preview not displaying properly on mobile?
                    </span>
                    <div class="flex items-center gap-2">
                        <a
                            :href="document.preview_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-surface-600 bg-white dark:bg-surface-900 text-slate-700 dark:text-surface-200 hover:border-[#1f6f46] hover:text-[#1f6f46] font-medium transition-colors"
                        >
                            <i class="pi pi-external-link text-[11px]"></i>
                            <span>Open in New Tab</span>
                        </a>
                        <a
                            :href="route('documents.download', document.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#1f6f46] hover:bg-[#185a38] text-white font-medium transition-colors"
                        >
                            <i class="pi pi-download text-[11px]"></i>
                            <span>Download</span>
                        </a>
                    </div>
                </div>

                <iframe
                    :src="document.preview_url"
                    class="w-full h-[65vh] rounded-xl border border-slate-200 dark:border-surface-700 bg-white"
                />
            </div>
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
