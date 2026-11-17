<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import AppLayout from '@/Layouts/AppLayout.vue';
import WelcomeBanner from '@/Components/Dashboard/WelcomeBanner.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import DocumentTable from '@/Components/Documents/DocumentTable.vue';
import DocumentCreateDialog from '@/Components/Documents/DocumentCreateDialog.vue';
import DocumentEditorDialog from '@/Components/Documents/DocumentEditorDialog.vue';
import DocumentLogsDialog from '@/Components/Documents/DocumentLogsDialog.vue';
import DocumentRenameDialog from '@/Components/Documents/DocumentRenameDialog.vue';
import DocumentAssignTeamsDialog from '@/Components/Documents/DocumentAssignTeamsDialog.vue';
import DocumentMoveFolderDialog from '@/Components/Documents/DocumentMoveFolderDialog.vue';
import DocumentDeleteDialog from '@/Components/Documents/DocumentDeleteDialog.vue';
import DocumentShareDialog from '@/Components/Documents/DocumentShareDialog.vue';
import DocumentUploadConfirmDialog from '@/Components/Documents/DocumentUploadConfirmDialog.vue';
import { useAuth } from '@/composables/useAuth';
import { useDocumentActions } from '@/composables/useDocumentActions';
import { useLocale } from '@/composables/useLocale';
import { getInitials } from '@/utils/formatters';

const props = defineProps({
    documents: { type: Array, default: () => [] },
    folders: { type: Array, default: () => [] },
    allowedExtensions: { type: Array, default: () => ['txt', 'md'] },
    creatableExtensions: { type: Array, default: () => ['txt', 'md'] },
    extensionMaxSizes: { type: Object, default: () => ({}) },
    maxFileUploads: { type: Number, default: 20 },
    storageQuota: { type: Object, default: () => ({ used_bytes: 0, limit_mb: 100, limit_bytes: 104857600 }) },
    teams: { type: Array, default: () => [] },
    myTeam: { type: Object, default: null },
    pendingJoinRequest: { type: Object, default: null },
});

const myTeamDialogVisible = ref(false);
const myTeamLeaderNames = computed(() =>
    (props.myTeam?.members ?? [])
        .filter((m) => m.is_team_leader)
        .map((m) => m.name)
        .join(', '),
);

const joinTeamDialogVisible = ref(false);
const joinConfirmDialogVisible = ref(false);
const joinTeamForm = useForm({
    team_code: '',
});

const openJoinConfirm = () => {
    if (!joinTeamForm.team_code.trim()) return;
    joinConfirmDialogVisible.value = true;
};

const submitJoinTeam = () => {
    joinTeamForm.post(route('team-join.request'), {
        preserveScroll: true,
        onError: () => {
            joinConfirmDialogVisible.value = false;
        },
        onSuccess: () => {
            joinConfirmDialogVisible.value = false;
            joinTeamDialogVisible.value = false;
            joinTeamForm.reset();
        },
    });
};

const page = usePage();
const { can, isSuperAdmin, isTeamLeader } = useAuth();
const { t } = useLocale();
const {
    uploading,
    creating,
    saving,
    renaming,
    assigningTeams,
    movingFolder,
    bulkDownloading,
    bulkDeleting,
    managingFolder,
    deleting,
    upload,
    create,
    save,
    rename,
    assignTeams,
    updateVisibility,
    toggleFavorite,
    toggleEncryption,
    moveFolder,
    bulkMoveFolder,
    bulkAssignTeams,
    bulkDelete,
    bulkDownload,
    createFolder,
    deleteFolder,
    createShareLink,
    updateSharePassword,
    revokeShareLink,
    destroy,
    download,
    sharing,
} = useDocumentActions();

const userName = computed(() => page.props.auth.user?.name ?? '');

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
    const isImage =
        Boolean(doc?.mime_type?.startsWith('image/')) ||
        ['.png', '.jpg', '.jpeg', '.webp', '.gif'].some((ext) => name.endsWith(ext));

    if (doc?.is_stored_file && !isPdf && !isDocx && !isImage && can('documents.download')) {
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

// --- Share dialog --------------------------------------------------------
const shareVisible = ref(false);
const selectedShareDocumentId = ref(null);
const selectedShareDocument = computed(
    () => props.documents.find((d) => Number(d.id) === Number(selectedShareDocumentId.value)) ?? null,
);

const openShare = (doc) => {
    selectedShareDocumentId.value = doc.id;
    shareVisible.value = true;
};

const submitGenerateShare = (payload) => {
    if (!selectedShareDocument.value) return;
    createShareLink(selectedShareDocument.value, payload);
};

const submitRevokeShare = (payload) => {
    if (!selectedShareDocument.value) return;
    revokeShareLink(selectedShareDocument.value, payload);
};

const submitUpdateSharePassword = (payload) => {
    if (!selectedShareDocument.value) return;
    updateSharePassword(selectedShareDocument.value, payload);
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

// --- Upload & Folders & Bulk Actions -------------------------------------
const tableRef = ref(null);
const uploadDialogVisible = ref(false);
const activeFolderFilter = ref('all'); // 'all' | 'uncategorized' | 'unassigned' | folder.id

const isUnassignedDoc = (doc) => doc?.visibility !== 'private' && (!doc?.teams || doc.teams.length === 0);

const folderFilteredDocuments = computed(() => {
    if (activeFolderFilter.value === 'all') return props.documents;
    if (activeFolderFilter.value === 'uncategorized') {
        return props.documents.filter((d) => !d.folder_id);
    }
    if (activeFolderFilter.value === 'unassigned') {
        return props.documents.filter(isUnassignedDoc);
    }
    return props.documents.filter((d) => Number(d.folder_id) === Number(activeFolderFilter.value));
});

const folderDocCount = (folderId) => {
    if (folderId === 'all') return props.documents.length;
    if (folderId === 'uncategorized') return props.documents.filter((d) => !d.folder_id).length;
    if (folderId === 'unassigned') return props.documents.filter(isUnassignedDoc).length;
    return props.documents.filter((d) => Number(d.folder_id) === Number(folderId)).length;
};

const newFolderDialogVisible = ref(false);
const newFolderName = ref('');
const submitCreateFolder = () => {
    const trimmed = newFolderName.value.trim();
    if (!trimmed) return;
    createFolder(trimmed, {
        onSuccess: () => {
            newFolderName.value = '';
            newFolderDialogVisible.value = false;
        },
    });
};

const deleteFolderVisible = ref(false);
const selectedDeleteFolder = ref(null);

const openDeleteFolder = (folder) => {
    selectedDeleteFolder.value = folder;
    deleteFolderVisible.value = true;
};

const confirmDeleteFolder = () => {
    const folder = selectedDeleteFolder.value;
    if (!folder) return;

    deleteFolder(folder, {
        onSuccess: () => {
            if (Number(activeFolderFilter.value) === Number(folder.id)) {
                activeFolderFilter.value = 'all';
            }
            deleteFolderVisible.value = false;
            selectedDeleteFolder.value = null;
        },
    });
};

// Move Folder (Single or Bulk)
const moveFolderVisible = ref(false);
const moveFolderTargetDoc = ref(null);
const moveFolderBulkIds = ref([]);

const openMoveFolder = (doc) => {
    moveFolderTargetDoc.value = doc;
    moveFolderBulkIds.value = [];
    moveFolderVisible.value = true;
};

const openBulkMoveFolder = (ids) => {
    moveFolderTargetDoc.value = null;
    moveFolderBulkIds.value = ids;
    moveFolderVisible.value = true;
};

const submitMoveFolder = (folderId) => {
    if (moveFolderBulkIds.value.length) {
        bulkMoveFolder(moveFolderBulkIds.value, folderId, {
            onSuccess: () => {
                moveFolderVisible.value = false;
                tableRef.value?.clearSelection();
            },
        });
    } else if (moveFolderTargetDoc.value) {
        moveFolder(moveFolderTargetDoc.value, folderId, {
            onSuccess: () => (moveFolderVisible.value = false),
        });
    }
};

// Bulk Assign Teams (Superadmin)
const bulkAssignIds = ref([]);
const openBulkAssignTeams = (ids) => {
    bulkAssignIds.value = ids;
    selectedAssignDocument.value = { id: 'bulk', name: `${ids.length} selected documents`, teams: [] };
    assignTeamsVisible.value = true;
};

const submitAssignTeamsOrBulk = ({ document, teamIds }) => {
    if (bulkAssignIds.value.length) {
        bulkAssignTeams(bulkAssignIds.value, teamIds, {
            onSuccess: () => {
                assignTeamsVisible.value = false;
                bulkAssignIds.value = [];
                tableRef.value?.clearSelection();
            },
        });
        return;
    }

    assignTeams(document, teamIds, { onSuccess: () => (assignTeamsVisible.value = false) });
};

// Bulk Delete Confirmation
const bulkDeleteVisible = ref(false);
const bulkDeleteIds = ref([]);
const openBulkDelete = (ids) => {
    bulkDeleteIds.value = ids;
    bulkDeleteVisible.value = true;
};
const confirmBulkDelete = () => {
    bulkDelete(bulkDeleteIds.value, {
        onSuccess: () => {
            bulkDeleteVisible.value = false;
            bulkDeleteIds.value = [];
            tableRef.value?.clearSelection();
        },
    });
};

const confirmUpload = ({ files, folderId, notifyMembers, encryptedFiles }) => {
    upload(files, {
        folderId,
        notifyMembers,
        encryptedFiles,
        onSuccess: () => {
            uploadDialogVisible.value = false;
        },
    });
};
</script>

<template>
    <Head :title="t('dashboard.page_title')" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <WelcomeBanner :name="userName" />

            <!-- Stats + Upload -->
            <div class="flex flex-wrap items-center gap-4">
                <StatCard icon="pi pi-file" :label="t('dashboard.total_documents')" :value="documents.length" />

                <button
                    v-if="myTeam"
                    type="button"
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm min-w-[14rem] text-left transition-colors hover:border-[#1f6f46] dark:hover:border-emerald-500/70 cursor-pointer"
                    @click="myTeamDialogVisible = true"
                >
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 shrink-0">
                        <i class="pi pi-users text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium text-slate-500 dark:text-surface-400">{{ t('dashboard.my_team') }}</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-surface-0 truncate">
                            {{ myTeam.name }}
                        </p>
                        <p class="text-[11px] font-medium text-[#1f6f46] dark:text-emerald-400">
                            {{ t('dashboard.team_members_count', { count: myTeam.members?.length ?? 0 }) }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-surface-400 truncate mt-0.5">
                            {{
                                myTeamLeaderNames
                                    ? t('dashboard.team_leader_label', { name: myTeamLeaderNames })
                                    : t('dashboard.no_team_leader')
                            }}
                        </p>
                    </div>
                    <i class="pi pi-chevron-right text-xs text-slate-400"></i>
                </button>

                <!-- No Team Assigned Card (for non-superadmin users removed from / without a team) -->
                <div
                    v-else-if="!isSuperAdmin"
                    class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/70 shadow-sm min-w-[16rem] max-w-md"
                >
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 shrink-0">
                            <i class="pi pi-users text-base"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 dark:text-surface-0">
                                {{ t('dashboard.no_team_title') }}
                            </p>
                            <template v-if="pendingJoinRequest">
                                <p class="text-xs font-medium text-amber-800 dark:text-amber-200 mt-0.5">
                                    {{ t('dashboard.no_team_pending', { team: pendingJoinRequest.team_name, code: pendingJoinRequest.team_code }) }}
                                </p>
                                <p class="text-[11px] text-slate-600 dark:text-surface-300 mt-0.5">
                                    {{ pendingJoinRequest.has_team_leader ? t('dashboard.no_team_pending_leader') : t('dashboard.no_team_pending_admin') }}
                                </p>
                            </template>
                            <p v-else class="text-xs text-slate-600 dark:text-surface-300 mt-0.5">
                                {{ t('dashboard.no_team_desc') }}
                            </p>
                        </div>
                    </div>

                    <Button
                        v-if="!pendingJoinRequest"
                        :label="t('dashboard.join_team')"
                        icon="pi pi-sign-in"
                        size="small"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl shrink-0"
                        @click="joinTeamDialogVisible = true"
                    />
                </div>

                <div v-if="can('documents.create') || can('documents.upload')" class="sm:ml-auto flex flex-wrap items-center gap-3">
                    <Button
                        v-if="can('documents.create')"
                        :label="t('dashboard.create_file')"
                        icon="pi pi-plus"
                        severity="secondary"
                        variant="outlined"
                        class="!border-[#1f6f46] !text-[#1f6f46] hover:!bg-emerald-50 dark:!border-emerald-400 dark:!text-emerald-400 dark:hover:!bg-emerald-950/60 !font-semibold !rounded-xl !px-5 !py-3"
                        @click="createVisible = true"
                    />
                    <Button
                        v-if="can('documents.upload')"
                        :label="t('dashboard.upload_file')"
                        icon="pi pi-cloud-upload"
                        :loading="uploading"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-6 !py-3"
                        @click="uploadDialogVisible = true"
                    />
                </div>
            </div>

            <!-- Folder Filter Bar -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-colors cursor-pointer"
                    :class="
                        activeFolderFilter === 'all'
                            ? 'bg-[#1f6f46] text-white border-[#1f6f46]'
                            : 'bg-white dark:bg-surface-900 text-slate-600 dark:text-surface-300 border-slate-200 dark:border-surface-700 hover:border-[#1f6f46]'
                    "
                    @click="activeFolderFilter = 'all'"
                >
                    <i class="pi pi-th-large text-[11px]"></i>
                    <span>{{ t('dashboard.all') }} ({{ folderDocCount('all') }})</span>
                </button>

                <button
                    v-for="folder in folders"
                    :key="folder.id"
                    type="button"
                    class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-colors cursor-pointer"
                    :class="
                        Number(activeFolderFilter) === Number(folder.id)
                            ? 'bg-[#1f6f46] text-white border-[#1f6f46]'
                            : 'bg-white dark:bg-surface-900 text-slate-600 dark:text-surface-300 border-slate-200 dark:border-surface-700 hover:border-[#1f6f46]'
                    "
                    @click="activeFolderFilter = folder.id"
                >
                    <i class="pi pi-folder text-[11px]"></i>
                    <span>{{ folder.name }} ({{ folderDocCount(folder.id) }})</span>
                    <span
                        class="ml-1 opacity-60 hover:opacity-100"
                        :title="t('dashboard.delete_folder_tooltip')"
                        @click.stop="openDeleteFolder(folder)"
                    >
                        <i class="pi pi-times text-[10px]"></i>
                    </span>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-colors cursor-pointer"
                    :class="
                        activeFolderFilter === 'uncategorized'
                            ? 'bg-[#1f6f46] text-white border-[#1f6f46]'
                            : 'bg-white dark:bg-surface-900 text-slate-600 dark:text-surface-300 border-slate-200 dark:border-surface-700 hover:border-[#1f6f46]'
                    "
                    @click="activeFolderFilter = 'uncategorized'"
                >
                    <span>{{ t('dashboard.uncategorized') }} ({{ folderDocCount('uncategorized') }})</span>
                </button>

                <button
                    v-if="isSuperAdmin"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border transition-colors cursor-pointer"
                    :class="
                        activeFolderFilter === 'unassigned'
                            ? 'bg-amber-600 text-white border-amber-600'
                            : 'bg-white dark:bg-surface-900 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800/70 hover:border-amber-500'
                    "
                    @click="activeFolderFilter = 'unassigned'"
                >
                    <i class="pi pi-exclamation-circle text-[11px]"></i>
                    <span>{{ t('dashboard.unassigned') }} ({{ folderDocCount('unassigned') }})</span>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold border border-dashed border-slate-300 dark:border-surface-700 text-slate-600 dark:text-surface-300 hover:border-[#1f6f46] hover:text-[#1f6f46] cursor-pointer transition-colors"
                    @click="newFolderDialogVisible = true"
                >
                    <i class="pi pi-folder-plus text-[11px]"></i>
                    <span>{{ t('dashboard.new_folder') }}</span>
                </button>
            </div>

            <DocumentTable
                ref="tableRef"
                :documents="folderFilteredDocuments"
                :bulk-downloading="bulkDownloading"
                :bulk-deleting="bulkDeleting"
                @open="openEditor"
                @logs="openLogs"
                @download="download"
                @rename="openRename"
                @assign-teams="openAssignTeams"
                @toggle-visibility="onToggleVisibility"
                @toggle-favorite="toggleFavorite"
                @toggle-encryption="toggleEncryption"
                @move-folder="openMoveFolder"
                @bulk-download="bulkDownload"
                @bulk-delete="openBulkDelete"
                @bulk-move-folder="openBulkMoveFolder"
                @bulk-assign-teams="openBulkAssignTeams"
                @share="openShare"
                @delete="openDelete"
            />
        </div>

        <DocumentCreateDialog
            v-model:visible="createVisible"
            :extensions="allowedExtensions"
            :creatable-extensions="creatableExtensions"
            :extension-max-sizes="extensionMaxSizes"
            :folders="folders"
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

        <DocumentShareDialog
            v-model:visible="shareVisible"
            :document="selectedShareDocument"
            :loading="sharing"
            @generate="submitGenerateShare"
            @update-password="submitUpdateSharePassword"
            @revoke="submitRevokeShare"
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
            @submit="submitAssignTeamsOrBulk"
        />

        <DocumentMoveFolderDialog
            v-model:visible="moveFolderVisible"
            :folders="folders"
            :initial-folder-id="moveFolderTargetDoc?.folder_id ?? null"
            :count="moveFolderBulkIds.length || 1"
            :loading="movingFolder"
            @submit="submitMoveFolder"
        />

        <DocumentDeleteDialog
            v-model:visible="deleteVisible"
            :document="selectedDeleteDocument"
            :loading="deleting"
            @confirm="confirmDelete"
        />

        <DocumentUploadConfirmDialog
            v-model:visible="uploadDialogVisible"
            :allowed-extensions="allowedExtensions"
            :extension-max-sizes="extensionMaxSizes"
            :max-file-uploads="maxFileUploads"
            :folders="folders"
            :loading="uploading"
            @confirm="confirmUpload"
        />

        <!-- My Team Members Dialog -->
        <Dialog
            v-model:visible="myTeamDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '30rem' }"
            :breakpoints="{ '640px': '94vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-users text-[#1f6f46] dark:text-emerald-400"></i>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-surface-0 leading-tight">
                            {{ myTeam?.name || t('dashboard.my_team') }}
                        </h3>
                        <p v-if="myTeam" class="text-xs text-slate-500 dark:text-surface-400">
                            {{ t('dashboard.team_members_count', { count: myTeam.members?.length ?? 0 }) }}
                            ·
                            <span class="font-medium text-[#1f6f46] dark:text-emerald-400">
                                {{
                                    myTeamLeaderNames
                                        ? t('dashboard.team_leader_label', { name: myTeamLeaderNames })
                                        : t('dashboard.no_team_leader')
                                }}
                            </span>
                        </p>
                    </div>
                </div>
            </template>

            <div v-if="!myTeam?.members?.length" class="py-8 text-center text-sm text-slate-500 dark:text-surface-400">
                {{ t('dashboard.no_team_members') }}
            </div>

            <ul v-else class="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-surface-800">
                <li
                    v-for="member in myTeam.members"
                    :key="member.id"
                    class="flex items-center gap-3 py-3 first:pt-1 last:pb-1"
                >
                    <span class="flex items-center justify-center w-9 h-9 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 text-xs font-bold shrink-0">
                        {{ getInitials(member.name) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-sm font-semibold text-slate-800 dark:text-surface-0 truncate">
                                {{ member.name }}
                            </span>
                            <span
                                v-if="member.is_me"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 dark:bg-surface-800 dark:text-surface-300"
                            >
                                {{ t('dashboard.you_badge') }}
                            </span>
                            <span
                                v-if="member.is_team_leader"
                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300"
                            >
                                {{ t('dashboard.team_lead_badge') }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-surface-400 truncate">
                            {{ member.email }}
                        </p>
                    </div>
                </li>
            </ul>

            <template #footer>
                <Button :label="t('common.close')" severity="secondary" variant="outlined" @click="myTeamDialogVisible = false" />
            </template>
        </Dialog>

        <!-- Join Team Dialog -->
        <Dialog
            v-model:visible="joinTeamDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '28rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eaf7f0] dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400">
                        <i class="pi pi-users text-sm"></i>
                    </span>
                    <span class="text-lg font-bold">{{ t('dashboard.join_team_title') }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" @submit.prevent="openJoinConfirm">
                <div class="flex flex-col gap-1.5">
                    <label for="join-team-code" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        {{ t('dashboard.join_team_code_label') }}
                    </label>
                    <InputText
                        id="join-team-code"
                        v-model="joinTeamForm.team_code"
                        :placeholder="t('dashboard.join_team_code_placeholder')"
                        autofocus
                        required
                        fluid
                        :invalid="Boolean(joinTeamForm.errors.team_code)"
                        class="!rounded-xl"
                    />
                    <small v-if="joinTeamForm.errors.team_code" class="text-red-500 text-xs">
                        {{ joinTeamForm.errors.team_code }}
                    </small>
                </div>

                <div class="flex items-start gap-2 rounded-xl border border-emerald-200/80 dark:border-emerald-800/70 bg-emerald-50/70 dark:bg-emerald-950/40 px-3 py-2.5 text-xs text-[#1f5137] dark:text-emerald-200">
                    <i class="pi pi-info-circle text-[#1f6f46] dark:text-emerald-400 mt-0.5 shrink-0"></i>
                    <span>{{ t('dashboard.join_team_info') }}</span>
                </div>
            </form>

            <template #footer>
                <Button
                    :label="t('common.cancel')"
                    severity="secondary"
                    variant="outlined"
                    :disabled="joinTeamForm.processing"
                    @click="joinTeamDialogVisible = false"
                />
                <Button
                    :label="t('dashboard.join_team_submit')"
                    icon="pi pi-send"
                    :disabled="!joinTeamForm.team_code.trim() || joinTeamForm.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    @click="openJoinConfirm"
                />
            </template>
        </Dialog>

        <!-- Join Team Confirm Dialog -->
        <Dialog
            v-model:visible="joinConfirmDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '26rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-exclamation-circle text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold">{{ t('dashboard.join_team_confirm_title') }}</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed pt-1">
                {{ t('dashboard.join_team_confirm_desc', { code: joinTeamForm.team_code.trim() }) }}
            </p>

            <template #footer>
                <Button
                    :label="t('common.cancel')"
                    severity="secondary"
                    variant="outlined"
                    :disabled="joinTeamForm.processing"
                    @click="joinConfirmDialogVisible = false"
                />
                <Button
                    :label="t('dashboard.join_team_confirm_submit')"
                    icon="pi pi-check"
                    :loading="joinTeamForm.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    @click="submitJoinTeam"
                />
            </template>
        </Dialog>

        <!-- New Folder Dialog -->
        <Dialog
            v-model:visible="newFolderDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '24rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-folder-plus text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dashboard.new_folder_title') }}</span>
                </div>
            </template>
            <form id="new-folder-form" class="space-y-3 pt-1" @submit.prevent="submitCreateFolder">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">{{ t('dashboard.folder_name_label') }}</label>
                    <InputText
                        v-model="newFolderName"
                        :placeholder="t('dashboard.folder_name_placeholder')"
                        autofocus
                        required
                        fluid
                        class="!rounded-xl"
                    />
                </div>
            </form>
            <template #footer>
                <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="newFolderDialogVisible = false" />
                <Button
                    type="submit"
                    form="new-folder-form"
                    :label="t('common.create')"
                    icon="pi pi-check"
                    :loading="managingFolder"
                    :disabled="!newFolderName.trim()"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                />
            </template>
        </Dialog>

        <!-- Delete Folder Confirm Dialog -->
        <Dialog
            v-model:visible="deleteFolderVisible"
            modal
            :draggable="false"
            :style="{ width: '26rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-exclamation-triangle text-red-500"></i>
                    <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dashboard.delete_folder_title') }}</span>
                </div>
            </template>
            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                {{ t('dashboard.delete_folder_confirm', { name: selectedDeleteFolder?.name }) }}
            </p>
            <template #footer>
                <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="deleteFolderVisible = false" />
                <Button
                    :label="t('common.delete')"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="managingFolder"
                    @click="confirmDeleteFolder"
                />
            </template>
        </Dialog>

        <!-- Bulk Delete Confirm Dialog -->
        <Dialog
            v-model:visible="bulkDeleteVisible"
            modal
            :draggable="false"
            :style="{ width: '26rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-exclamation-triangle text-red-500"></i>
                    <span class="text-lg font-bold text-slate-800 dark:text-surface-0">Delete Selected Documents</span>
                </div>
            </template>
            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                Are you sure you want to delete
                <span class="font-semibold text-slate-900 dark:text-surface-0">{{ bulkDeleteIds.length }}</span>
                selected documents? This action cannot be undone.
            </p>
            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="bulkDeleteVisible = false" />
                <Button
                    label="Delete All"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="bulkDeleting"
                    @click="confirmBulkDelete"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
