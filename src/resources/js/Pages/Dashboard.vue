<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AppLayout from '@/Layouts/AppLayout.vue';
import WelcomeBanner from '@/Components/Dashboard/WelcomeBanner.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import DocumentTable from '@/Components/Documents/DocumentTable.vue';
import DocumentCreateDialog from '@/Components/Documents/DocumentCreateDialog.vue';
import DocumentEditorDialog from '@/Components/Documents/DocumentEditorDialog.vue';
import DocumentLogsDialog from '@/Components/Documents/DocumentLogsDialog.vue';
import DocumentRenameDialog from '@/Components/Documents/DocumentRenameDialog.vue';
import DocumentAssignTeamsDialog from '@/Components/Documents/DocumentAssignTeamsDialog.vue';
import DocumentDeleteDialog from '@/Components/Documents/DocumentDeleteDialog.vue';
import DocumentUploadConfirmDialog from '@/Components/Documents/DocumentUploadConfirmDialog.vue';
import { useAuth } from '@/composables/useAuth';
import { useDocumentActions } from '@/composables/useDocumentActions';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    allowedExtensions: { type: Array, default: () => ['txt', 'md'] },
    extensionMaxSizes: { type: Object, default: () => ({}) },
    teams: { type: Array, default: () => [] },
});

const page = usePage();
const { can } = useAuth();
const {
    uploading,
    creating,
    saving,
    renaming,
    assigningTeams,
    deleting,
    upload,
    create,
    save,
    rename,
    assignTeams,
    updateVisibility,
    destroy,
    download,
} = useDocumentActions();

const userName = computed(() => page.props.auth.user?.name ?? '');
const acceptExtensions = computed(() => props.allowedExtensions.map((ext) => `.${ext}`).join(','));

// --- Create dialog -------------------------------------------------------
const createVisible = ref(false);

const submitCreate = (payload) => {
    create(payload, { onSuccess: () => (createVisible.value = false) });
};

// --- Editor dialog -------------------------------------------------------
const editorVisible = ref(false);
const selectedDocument = ref(null);

const openEditor = (doc) => {
    const name = String(doc?.name ?? '').toLowerCase();
    const isPdf = doc?.mime_type === 'application/pdf' || name.endsWith('.pdf');
    const isDocx = name.endsWith('.docx');

    if (doc?.is_stored_file && !isPdf && !isDocx && can('documents.download')) {
        download(doc);
        return;
    }

    selectedDocument.value = doc;
    editorVisible.value = true;
};

const saveDocument = (doc) => save(doc, { onSuccess: () => (editorVisible.value = false) });

// --- Logs dialog ---------------------------------------------------------
const logsVisible = ref(false);
const selectedLogDocument = ref(null);

const openLogs = (doc) => {
    selectedLogDocument.value = doc;
    logsVisible.value = true;
};

// --- Rename dialog -------------------------------------------------------
const renameVisible = ref(false);
const selectedRenameDocument = ref(null);

const openRename = (doc) => {
    selectedRenameDocument.value = doc;
    renameVisible.value = true;
};

const submitRename = ({ document, name }) => {
    rename(document, name, { onSuccess: () => (renameVisible.value = false) });
};

// --- Assign Teams dialog (Superadmin) ------------------------------------
const assignTeamsVisible = ref(false);
const selectedAssignDocument = ref(null);

const openAssignTeams = (doc) => {
    selectedAssignDocument.value = doc;
    assignTeamsVisible.value = true;
};

const submitAssignTeams = ({ document, teamIds }) => {
    assignTeams(document, teamIds, { onSuccess: () => (assignTeamsVisible.value = false) });
};

const onToggleVisibility = ({ document, visibility }) => {
    updateVisibility(document, visibility);
};

// --- Delete dialog -------------------------------------------------------
const deleteVisible = ref(false);
const selectedDeleteDocument = ref(null);

const openDelete = (doc) => {
    selectedDeleteDocument.value = doc;
    deleteVisible.value = true;
};

const confirmDelete = (doc) => {
    destroy(doc, { onSuccess: () => (deleteVisible.value = false) });
};

// --- Upload --------------------------------------------------------------
const fileInput = ref(null);
const uploadConfirmVisible = ref(false);
const pendingUploadFiles = ref([]);

const onFileSelected = (event) => {
    const files = Array.from(event.target.files ?? []);
    if (files.length) {
        pendingUploadFiles.value = files;
        uploadConfirmVisible.value = true;
    }

    // reset supaya file yang sama bisa dipilih lagi
    event.target.value = '';
};

const confirmUpload = (files) => {
    upload(files, {
        onSuccess: () => {
            uploadConfirmVisible.value = false;
            pendingUploadFiles.value = [];
        },
    });
};
</script>

<template>
    <Head title="Documents" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <WelcomeBanner :name="userName" />

            <!-- Stats + Upload -->
            <div class="flex flex-wrap items-center gap-4">
                <StatCard icon="pi pi-file" label="Total Documents" :value="documents.length" />

                <input
                    ref="fileInput"
                    type="file"
                    :accept="acceptExtensions"
                    multiple
                    class="hidden"
                    @change="onFileSelected"
                />

                <div v-if="can('documents.create') || can('documents.upload')" class="sm:ml-auto flex flex-wrap items-center gap-3">
                    <Button
                        v-if="can('documents.create')"
                        label="Create File"
                        icon="pi pi-plus"
                        severity="secondary"
                        variant="outlined"
                        class="!border-[#1f6f46] !text-[#1f6f46] hover:!bg-emerald-50 dark:!border-emerald-400 dark:!text-emerald-400 dark:hover:!bg-emerald-950/60 !font-semibold !rounded-xl !px-5 !py-3"
                        @click="createVisible = true"
                    />
                    <Button
                        v-if="can('documents.upload')"
                        label="Upload File"
                        icon="pi pi-cloud-upload"
                        :loading="uploading"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-6 !py-3"
                        @click="fileInput.click()"
                    />
                </div>
            </div>

            <DocumentTable
                :documents="documents"
                @open="openEditor"
                @logs="openLogs"
                @download="download"
                @rename="openRename"
                @assign-teams="openAssignTeams"
                @toggle-visibility="onToggleVisibility"
                @delete="openDelete"
            />
        </div>

        <DocumentCreateDialog
            v-model:visible="createVisible"
            :extensions="allowedExtensions"
            :extension-max-sizes="extensionMaxSizes"
            :loading="creating"
            @submit="submitCreate"
        />

        <DocumentEditorDialog
            v-model:visible="editorVisible"
            :document="selectedDocument"
            :saving="saving"
            :can-edit="can('documents.edit')"
            @save="saveDocument"
        />

        <DocumentLogsDialog
            v-model:visible="logsVisible"
            :document="selectedLogDocument"
        />

        <DocumentRenameDialog
            v-model:visible="renameVisible"
            :document="selectedRenameDocument"
            :extensions="allowedExtensions"
            :loading="renaming"
            @submit="submitRename"
        />

        <DocumentAssignTeamsDialog
            v-model:visible="assignTeamsVisible"
            :document="selectedAssignDocument"
            :teams="teams"
            :loading="assigningTeams"
            @submit="submitAssignTeams"
        />

        <DocumentDeleteDialog
            v-model:visible="deleteVisible"
            :document="selectedDeleteDocument"
            :loading="deleting"
            @confirm="confirmDelete"
        />

        <DocumentUploadConfirmDialog
            v-model:visible="uploadConfirmVisible"
            :files="pendingUploadFiles"
            :extension-max-sizes="extensionMaxSizes"
            :loading="uploading"
            @confirm="confirmUpload"
        />
    </AppLayout>
</template>
