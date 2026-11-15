<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Editor from 'primevue/editor';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

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
const notifyMembers = ref(false);

const folderOptions = computed(() => [
    { id: null, name: t('dashboard.uncategorized') },
    ...props.folders,
]);

const visibilityOptions = computed(() => [
    { label: 'Publish', value: 'publish' },
    { label: 'Private', value: 'private' },
]);

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
            notifyMembers.value = false;
        }
    },
);

watch(visibility, (val) => {
    if (val === 'private') {
        notifyMembers.value = false;
    }
});

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
        notify_members: visibility.value === 'publish' ? notifyMembers.value : false,
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
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.create_title') }}</span>
            </div>
        </template>

        <form class="space-y-4 pt-1" @submit.prevent="onSubmit">
            <!-- File Name + Extension + Visibility -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-6 flex flex-col gap-1.5">
                    <label for="create-doc-name" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        {{ t('dialogs.file_name') }}
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
                        {{ t('dialogs.extension') }}
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
                        {{ t('dialogs.visibility') }}
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
                    {{ t('dialogs.folder') }}
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
                    {{ t('dialogs.content_optional') }}
                </label>
                <Editor v-model="content" editorStyle="height: 16rem" />
            </div>

            <!-- Notify Team Members -->
            <div v-if="visibility === 'publish'" class="flex items-center gap-2.5 pt-1">
                <Checkbox v-model="notifyMembers" inputId="create-notify-members" binary />
                <label for="create-notify-members" class="text-sm text-slate-700 dark:text-surface-200 cursor-pointer select-none">
                    {{ t('dialogs.notify_members_create') }}
                </label>
            </div>
        </form>

        <template #footer>
            <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="close" />
            <Button
                :label="t('common.create')"
                icon="pi pi-check"
                :loading="loading"
                :disabled="!isValid"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold"
                @click="onSubmit"
            />
        </template>
    </Dialog>
</template>
