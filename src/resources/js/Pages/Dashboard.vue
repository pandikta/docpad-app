<script setup>
import { ref, computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import AppLayout from '@/Layouts/AppLayout.vue';
import WelcomeBanner from '@/Components/Dashboard/WelcomeBanner.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import DocumentTable from '@/Components/Documents/DocumentTable.vue';
import DocumentEditorDialog from '@/Components/Documents/DocumentEditorDialog.vue';
import DocumentLogsDialog from '@/Components/Documents/DocumentLogsDialog.vue';
import DocumentRenameDialog from '@/Components/Documents/DocumentRenameDialog.vue';
import DocumentDeleteDialog from '@/Components/Documents/DocumentDeleteDialog.vue';
import { useDocumentActions } from '@/composables/useDocumentActions';

defineProps({
    documents: { type: Array, default: () => [] },
});

const page = usePage();
const { uploading, saving, renaming, deleting, upload, save, rename, destroy, download } = useDocumentActions();

const userName = computed(() => page.props.auth.user?.name ?? '');

// --- Editor dialog -------------------------------------------------------
const editorVisible = ref(false);
const selectedDocument = ref(null);

const openEditor = (doc) => {
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

const onFileSelected = (event) => {
    const files = Array.from(event.target.files ?? []);
    if (files.length) upload(files);

    // reset supaya file yang sama bisa dipilih lagi
    event.target.value = '';
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

                <input ref="fileInput" type="file" accept=".txt,.md" multiple class="hidden" @change="onFileSelected" />
                <Button
                    label="Upload File"
                    icon="pi pi-cloud-upload"
                    :loading="uploading"
                    class="sm:ml-auto !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-6 !py-3"
                    @click="fileInput.click()"
                />
            </div>

            <DocumentTable
                :documents="documents"
                @open="openEditor"
                @logs="openLogs"
                @download="download"
                @rename="openRename"
                @delete="openDelete"
            />
        </div>

        <DocumentEditorDialog
            v-model:visible="editorVisible"
            :document="selectedDocument"
            :saving="saving"
            @save="saveDocument"
        />

        <DocumentLogsDialog
            v-model:visible="logsVisible"
            :document="selectedLogDocument"
        />

        <DocumentRenameDialog
            v-model:visible="renameVisible"
            :document="selectedRenameDocument"
            :loading="renaming"
            @submit="submitRename"
        />

        <DocumentDeleteDialog
            v-model:visible="deleteVisible"
            :document="selectedDeleteDocument"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
