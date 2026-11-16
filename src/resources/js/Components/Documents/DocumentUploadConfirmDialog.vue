<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import { formatFileSize } from '@/utils/formatters';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    visible: { type: Boolean, default: false },
    allowedExtensions: { type: Array, default: () => ['txt', 'md'] },
    extensionMaxSizes: { type: Object, default: () => ({}) },
    folders: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'confirm']);

const fileInput = ref(null);
const selectedFiles = ref([]);
const folderId = ref(null);
const isDragging = ref(false);
const notifyMembers = ref(false);
const encryptedMap = ref({});

const BINARY_EXTENSIONS = new Set(['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'webp', 'gif']);
const fileKey = (file) => `${file.name}-${file.size}-${file.lastModified}`;
const isBinaryFile = (file) => BINARY_EXTENSIONS.has(getFileExtension(file?.name));
const toggleFileEncrypt = (file) => {
    const key = fileKey(file);
    encryptedMap.value = {
        ...encryptedMap.value,
        [key]: !encryptedMap.value[key],
    };
};

const folderOptions = computed(() => [
    { id: null, name: t('dashboard.uncategorized') },
    ...props.folders,
]);

const acceptExtensions = computed(() =>
    props.allowedExtensions.map((ext) => `.${ext}`).join(','),
);

watch(
    () => props.visible,
    (isOpen) => {
        if (isOpen) {
            selectedFiles.value = [];
            folderId.value = null;
            isDragging.value = false;
            notifyMembers.value = false;
            encryptedMap.value = {};
        }
    },
);

const close = () => emit('update:visible', false);

const getFileExtension = (fileName) => {
    const parts = String(fileName ?? '').split('.');
    return parts.length > 1 ? parts.pop().toLowerCase() : '';
};

const getMaxSizeKb = (ext) => Number(props.extensionMaxSizes?.[ext] ?? 1024);

const formatMaxKb = (kb) => {
    if (kb >= 1024) {
        const mb = kb / 1024;
        return `${Number.isInteger(mb) ? mb : mb.toFixed(1)} MB`;
    }
    return `${kb} KB`;
};

const isExtensionAllowed = (file) => {
    const ext = getFileExtension(file?.name);
    return props.allowedExtensions.includes(ext);
};

const isOversized = (file) => {
    const ext = getFileExtension(file?.name);
    return Number(file?.size ?? 0) > getMaxSizeKb(ext) * 1024;
};

const hasFileIssue = (file) => !isExtensionAllowed(file) || isOversized(file);

const addFiles = (incomingFiles) => {
    const list = Array.from(incomingFiles ?? []);
    if (!list.length) return;

    const existingKeys = new Set(selectedFiles.value.map((f) => `${f.name}-${f.size}-${f.lastModified}`));
    const merged = [...selectedFiles.value];

    for (const file of list) {
        const key = `${file.name}-${file.size}-${file.lastModified}`;
        if (!existingKeys.has(key)) {
            merged.push(file);
            existingKeys.add(key);
        }
    }

    selectedFiles.value = merged;
};

const onFileSelected = (event) => {
    addFiles(event.target.files);
    event.target.value = '';
};

const onDrop = (event) => {
    isDragging.value = false;
    addFiles(event.dataTransfer?.files);
};

const removeFile = (index) => {
    selectedFiles.value = selectedFiles.value.filter((_, idx) => idx !== index);
};

const onConfirm = () => {
    if (!selectedFiles.value.length) return;
    emit('confirm', {
        files: selectedFiles.value,
        folderId: folderId.value,
        notifyMembers: notifyMembers.value,
        encryptedFiles: selectedFiles.value.map((file) => Boolean(encryptedMap.value[fileKey(file)])),
    });
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :draggable="false"
        :style="{ width: '36rem' }"
        :breakpoints="{ '640px': '94vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <i class="pi pi-cloud-upload text-xl text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.upload_title') }}</span>
            </div>
        </template>

        <div class="space-y-4 pt-1">
            <!-- Allowed Extensions & Max Sizes Info -->
            <div class="rounded-xl border border-slate-200 dark:border-surface-700 bg-slate-50/80 dark:bg-surface-900/50 p-3.5 space-y-2">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-surface-200">
                    <i class="pi pi-info-circle text-[#1f6f46] dark:text-emerald-400"></i>
                    <span>{{ t('dialogs.upload_allowed') }}</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-for="ext in allowedExtensions"
                        :key="ext"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-white dark:bg-surface-800 border border-slate-200 dark:border-surface-700 text-slate-700 dark:text-surface-200"
                    >
                        <span class="font-semibold text-[#1f6f46] dark:text-emerald-400">.{{ ext }}</span>
                        <span class="text-slate-400">·</span>
                        <span class="text-slate-500 dark:text-surface-400">max {{ formatMaxKb(getMaxSizeKb(ext)) }}</span>
                    </span>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/50 dark:text-emerald-200">
                    <i class="pi pi-globe text-xs text-[#1f6f46] dark:text-emerald-400 shrink-0"></i>
                    <p>
                        All uploaded documents will be set to
                        <span class="font-bold text-[#1f6f46] dark:text-emerald-300">Publish</span>
                        and shared with your team.
                    </p>
                </div>
            </div>

            <!-- Optional Folder -->
            <div v-if="folders.length" class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                    Destination Folder <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <Select
                    v-model="folderId"
                    :options="folderOptions"
                    option-label="name"
                    option-value="id"
                    fluid
                    class="!rounded-xl"
                />
            </div>

            <!-- Hidden File Input -->
            <input
                ref="fileInput"
                type="file"
                :accept="acceptExtensions"
                multiple
                class="hidden"
                @change="onFileSelected"
            />

            <!-- Dropzone / Choose Files -->
            <div
                class="rounded-xl border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
                :class="
                    isDragging
                        ? 'border-[#1f6f46] bg-emerald-50/70 dark:bg-emerald-950/30'
                        : 'border-slate-300 dark:border-surface-700 hover:border-[#1f6f46] dark:hover:border-emerald-500/70 bg-white dark:bg-surface-900/30'
                "
                @click="fileInput?.click()"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
            >
                <i class="pi pi-cloud-upload text-3xl text-[#1f6f46] dark:text-emerald-400 mb-2"></i>
                <p class="text-sm font-semibold text-slate-700 dark:text-surface-100">
                    Click to choose files or drag &amp; drop here
                </p>
                <p class="text-xs text-slate-500 dark:text-surface-400 mt-1">
                    You can select one or multiple files at once
                </p>
            </div>

            <!-- Selected Files Listing -->
            <div v-if="selectedFiles.length" class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-semibold text-slate-700 dark:text-surface-200">
                        {{ t('dialogs.upload_selected', { count: selectedFiles.length }) }}
                    </span>
                    <button
                        type="button"
                        class="text-slate-500 hover:text-red-500 font-medium cursor-pointer"
                        @click="
                            selectedFiles = [];
                            encryptedMap = {};
                        "
                    >
                        {{ t('table.clear') }}
                    </button>
                </div>

                <ul class="max-h-44 overflow-y-auto rounded-xl border border-slate-200 dark:border-surface-700 bg-slate-50 dark:bg-surface-900/50 divide-y divide-slate-200/70 dark:divide-surface-700/70 text-xs">
                    <li
                        v-for="(file, idx) in selectedFiles"
                        :key="`${file.name}-${file.size}-${idx}`"
                        class="flex items-center justify-between gap-2 px-3 py-2"
                        :class="hasFileIssue(file) ? 'text-amber-700 dark:text-amber-300 bg-amber-50/70 dark:bg-amber-950/30' : 'text-slate-700 dark:text-surface-200'"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <i
                                class="text-xs shrink-0"
                                :class="hasFileIssue(file) ? 'pi pi-exclamation-triangle text-amber-500' : 'pi pi-file text-slate-400'"
                            ></i>
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ file.name }}</p>
                                <p v-if="!isExtensionAllowed(file)" class="text-[11px] text-amber-600 dark:text-amber-400">
                                    Extension not allowed (will be skipped)
                                </p>
                                <p v-else-if="isOversized(file)" class="text-[11px] text-amber-600 dark:text-amber-400">
                                    Exceeds max {{ formatMaxKb(getMaxSizeKb(getFileExtension(file.name))) }} (will be skipped)
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5 shrink-0">
                            <label
                                v-if="!hasFileIssue(file)"
                                v-tooltip.top="t('dialogs.encrypt_content_upload')"
                                class="inline-flex items-center gap-1.5 text-[11px] font-medium cursor-pointer select-none"
                                :class="
                                    encryptedMap[fileKey(file)]
                                        ? 'text-amber-700 dark:text-amber-300 font-semibold'
                                        : 'text-slate-500 dark:text-surface-400'
                                "
                                @click.stop
                            >
                                <Checkbox
                                    :modelValue="Boolean(encryptedMap[fileKey(file)])"
                                    binary
                                    @update:modelValue="toggleFileEncrypt(file)"
                                />
                                <span>Encrypt</span>
                            </label>
                            <span class="text-[11px] opacity-80">
                                {{ formatFileSize(file.size) }}
                            </span>
                            <button
                                type="button"
                                class="p-1 rounded hover:bg-slate-200/70 dark:hover:bg-surface-700 text-slate-400 hover:text-red-500 cursor-pointer"
                                aria-label="Remove file"
                                @click.stop="removeFile(idx)"
                            >
                                <i class="pi pi-times text-[10px]"></i>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Notify Team Members -->
            <div class="flex items-center gap-2.5 pt-1">
                <Checkbox v-model="notifyMembers" inputId="upload-notify-members" binary />
                <label for="upload-notify-members" class="text-sm text-slate-700 dark:text-surface-200 cursor-pointer select-none">
                    {{ t('dialogs.notify_members_upload') }}
                </label>
            </div>
        </div>

        <template #footer>
            <Button :label="t('common.cancel')" severity="secondary" variant="outlined" :disabled="loading" @click="close" />
            <Button
                :label="selectedFiles.length ? `${t('dialogs.upload_submit')} (${selectedFiles.length})` : t('dialogs.upload_submit')"
                icon="pi pi-cloud-upload"
                :loading="loading"
                :disabled="!selectedFiles.length"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                @click="onConfirm"
            />
        </template>
    </Dialog>
</template>
