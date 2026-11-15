<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Editor from 'primevue/editor';
import Button from 'primevue/button';

const props = defineProps({
    visible: { type: Boolean, default: false },
    extensions: { type: Array, default: () => ['txt', 'md'] },
    extensionMaxSizes: { type: Object, default: () => ({}) },
    folders: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'submit']);

const name = ref('');
const selectedExtension = ref('txt');
const visibility = ref('publish');
const folderId = ref(null);
const content = ref('');

const folderOptions = computed(() => [
    { id: null, name: 'Uncategorized (No Folder)' },
    ...props.folders,
]);

const visibilityOptions = [
    { label: 'Publish (Shared with Team)', value: 'publish' },
    { label: 'Private (Only Me)', value: 'private' },
];

const BINARY_EXTENSIONS = new Set(['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx']);

const editableExtensions = computed(() =>
    props.extensions.filter((ext) => !BINARY_EXTENSIONS.has(String(ext).toLowerCase())),
);

const extensionOptions = computed(() =>
    editableExtensions.value.map((ext) => ({
        label: `.${ext}`,
        value: ext,
    })),
);

const selectedMaxSizeLabel = computed(() => {
    const kb = Number(props.extensionMaxSizes?.[selectedExtension.value] ?? 1024);
    if (kb >= 1024) {
        const mb = kb / 1024;
        return `${Number.isInteger(mb) ? mb : mb.toFixed(1)} MB`;
    }
    return `${kb} KB`;
});

// Reset form setiap kali dialog dibuka
watch(
    () => props.visible,
    (isOpen) => {
        if (isOpen) {
            name.value = '';
            selectedExtension.value = editableExtensions.value[0] ?? 'txt';
            visibility.value = 'publish';
            folderId.value = null;
            content.value = '';
        }
    },
);

// Bersihkan akhiran ekstensi ganda jika user tidak sengaja mengetik ".txt" di kolom nama
const normalizedBaseName = computed(() => {
    let base = name.value.trim();
    const suffix = `.${selectedExtension.value}`.toLowerCase();
    if (base.toLowerCase().endsWith(suffix)) {
        base = base.slice(0, -suffix.length).trim();
    }
    return base;
});

const previewFileName = computed(() => {
    const base = normalizedBaseName.value || 'untitled';
    return `${base}.${selectedExtension.value}`;
});

const isValid = computed(() => normalizedBaseName.value.length > 0 && Boolean(selectedExtension.value));

const close = () => emit('update:visible', false);

const onSubmit = () => {
    if (!isValid.value) return;

    emit('submit', {
        name: normalizedBaseName.value,
        extension: selectedExtension.value,
        visibility: visibility.value,
        folder_id: folderId.value,
        content: content.value,
    });
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        maximizable
        :draggable="false"
        :style="{ width: '48rem' }"
        :breakpoints="{ '1199px': '80vw', '640px': '94vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <i class="pi pi-file-plus text-xl text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">Create New File</span>
            </div>
        </template>

        <form class="space-y-4 pt-1" @submit.prevent="onSubmit">
            <!-- File Name + Extension + Visibility -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-6 flex flex-col gap-1.5">
                    <label for="create-doc-name" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        File Name
                    </label>
                    <InputText
                        id="create-doc-name"
                        v-model="name"
                        placeholder="e.g. meeting-notes"
                        autofocus
                        required
                        fluid
                        class="!rounded-xl"
                    />
                </div>

                <div class="sm:col-span-2 flex flex-col gap-1.5">
                    <label for="create-doc-ext" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        Extension
                    </label>
                    <Select
                        input-id="create-doc-ext"
                        v-model="selectedExtension"
                        :options="extensionOptions"
                        option-label="label"
                        option-value="value"
                        fluid
                        class="!rounded-xl"
                    />
                </div>

                <div class="sm:col-span-4 flex flex-col gap-1.5">
                    <label for="create-doc-visibility" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        Visibility
                    </label>
                    <Select
                        input-id="create-doc-visibility"
                        v-model="visibility"
                        :options="visibilityOptions"
                        option-label="label"
                        option-value="value"
                        fluid
                        class="!rounded-xl"
                    />
                </div>
            </div>

            <!-- Optional Folder -->
            <div v-if="folders.length" class="flex flex-col gap-1.5">
                <label for="create-doc-folder" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    Folder <span class="text-xs font-normal text-slate-400">(optional)</span>
                </label>
                <Select
                    input-id="create-doc-folder"
                    v-model="folderId"
                    :options="folderOptions"
                    option-label="name"
                    option-value="id"
                    fluid
                    class="!rounded-xl"
                />
            </div>

            <!-- Live Preview Filename + Max Size Info -->
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-surface-400">
                <i class="pi pi-file text-[#1f6f46] dark:text-emerald-400"></i>
                <span>File will be saved as:</span>
                <span class="font-semibold text-slate-800 dark:text-surface-100 bg-slate-100 dark:bg-surface-800 px-2 py-0.5 rounded-md">
                    {{ previewFileName }}
                </span>
                <span class="sm:ml-auto inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-300 font-medium">
                    <i class="pi pi-info-circle text-[11px]"></i>
                    <span>Max size (.{{ selectedExtension }}): {{ selectedMaxSizeLabel }}</span>
                </span>
            </div>

            <!-- Content Editor (Optional) -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    Content <span class="text-xs font-normal text-slate-400">(optional)</span>
                </label>
                <Editor v-model="content" editorStyle="height: 16rem" />
            </div>
        </form>

        <template #footer>
            <Button label="Cancel" severity="secondary" variant="outlined" @click="close" />
            <Button
                label="Create File"
                icon="pi pi-check"
                :loading="loading"
                :disabled="!isValid"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold"
                @click="onSubmit"
            />
        </template>
    </Dialog>
</template>
