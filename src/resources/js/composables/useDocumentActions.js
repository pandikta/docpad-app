import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';

/**
 * Semua aksi dokumen (upload, save, rename, delete, download) ke backend ada di sini.
 * Pesan sukses ditampilkan otomatis oleh AppLayout (flash `success`),
 * jadi di sini hanya perlu menangani error.
 */
export function useDocumentActions() {
    const toast = useToast();

    const uploading = ref(false);
    const creating = ref(false);
    const saving = ref(false);
    const renaming = ref(false);
    const assigningTeams = ref(false);
    const updatingVisibility = ref(false);
    const movingFolder = ref(false);
    const bulkDownloading = ref(false);
    const bulkDeleting = ref(false);
    const managingFolder = ref(false);
    const deleting = ref(false);
    const sharing = ref(false);

    const notifyError = (summary, errors) => {
        toast.add({
            severity: 'error',
            summary,
            detail: Object.values(errors)[0] ?? 'Something went wrong.',
            life: 5000,
        });
    };

    const upload = (files, { folderId = null, notifyMembers = false, encryptedFiles = [], onSuccess } = {}) => {
        router.post(
            route('documents.store'),
            {
                files,
                folder_id: folderId,
                notify_members: notifyMembers ? 1 : 0,
                encrypted_files: encryptedFiles.map((val) => (val ? 1 : 0)),
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => (uploading.value = true),
                onSuccess,
                onError: (errors) => notifyError('Upload Failed', errors),
                onFinish: () => (uploading.value = false),
            },
        );
    };

    const create = (payload, { onSuccess } = {}) => {
        router.post(
            route('documents.create'),
            payload,
            {
                preserveScroll: true,
                onStart: () => (creating.value = true),
                onSuccess,
                onError: (errors) => notifyError('Create Failed', errors),
                onFinish: () => (creating.value = false),
            },
        );
    };

    const save = (document, { onSuccess } = {}) => {
        router.put(
            route('documents.update', document.id),
            { content: document.content },
            {
                preserveScroll: true,
                onStart: () => (saving.value = true),
                onSuccess,
                onError: (errors) => notifyError('Save Failed', errors),
                onFinish: () => (saving.value = false),
            },
        );
    };

    const rename = (document, name, { onSuccess } = {}) => {
        router.patch(
            route('documents.rename', document.id),
            { name },
            {
                preserveScroll: true,
                onStart: () => (renaming.value = true),
                onSuccess,
                onError: (errors) => notifyError('Rename Failed', errors),
                onFinish: () => (renaming.value = false),
            },
        );
    };

    const assignTeams = (document, teamIds, { onSuccess } = {}) => {
        router.put(
            route('documents.assign-teams', document.id),
            { team_ids: teamIds },
            {
                preserveScroll: true,
                onStart: () => (assigningTeams.value = true),
                onSuccess,
                onError: (errors) => notifyError('Assign Teams Failed', errors),
                onFinish: () => (assigningTeams.value = false),
            },
        );
    };

    const updateVisibility = (document, visibility, { onSuccess } = {}) => {
        router.patch(
            route('documents.visibility', document.id),
            { visibility },
            {
                preserveScroll: true,
                onStart: () => (updatingVisibility.value = true),
                onSuccess,
                onError: (errors) => notifyError('Visibility Update Failed', errors),
                onFinish: () => (updatingVisibility.value = false),
            },
        );
    };

    const destroy = (document, { onSuccess } = {}) => {
        router.delete(route('documents.destroy', document.id), {
            preserveScroll: true,
            onStart: () => (deleting.value = true),
            onSuccess,
            onError: (errors) => notifyError('Delete Failed', errors),
            onFinish: () => (deleting.value = false),
        });
    };

    const download = (document) => {
        window.location.href = route('documents.download', document.id);
    };

    const toggleFavorite = (document) => {
        router.patch(
            route('documents.favorite', document.id),
            {},
            {
                preserveScroll: true,
                onError: (errors) => notifyError('Favorite Failed', errors),
            },
        );
    };

    const toggleEncryption = (document) => {
        router.patch(
            route('documents.encryption', document.id),
            {},
            {
                preserveScroll: true,
                onError: (errors) => notifyError('Encryption Failed', errors),
            },
        );
    };

    const moveFolder = (document, folderId, { onSuccess } = {}) => {
        router.patch(
            route('documents.folder', document.id),
            { folder_id: folderId },
            {
                preserveScroll: true,
                onStart: () => (movingFolder.value = true),
                onSuccess,
                onError: (errors) => notifyError('Move Folder Failed', errors),
                onFinish: () => (movingFolder.value = false),
            },
        );
    };

    const bulkMoveFolder = (ids, folderId, { onSuccess } = {}) => {
        router.post(
            route('documents.bulk-folder'),
            { ids, folder_id: folderId },
            {
                preserveScroll: true,
                onStart: () => (movingFolder.value = true),
                onSuccess,
                onError: (errors) => notifyError('Move Folder Failed', errors),
                onFinish: () => (movingFolder.value = false),
            },
        );
    };

    const bulkAssignTeams = (ids, teamIds, { onSuccess } = {}) => {
        router.post(
            route('documents.bulk-teams'),
            { ids, team_ids: teamIds },
            {
                preserveScroll: true,
                onStart: () => (assigningTeams.value = true),
                onSuccess,
                onError: (errors) => notifyError('Assign Teams Failed', errors),
                onFinish: () => (assigningTeams.value = false),
            },
        );
    };

    const bulkDelete = (ids, { onSuccess } = {}) => {
        router.post(
            route('documents.bulk-delete'),
            { ids },
            {
                preserveScroll: true,
                onStart: () => (bulkDeleting.value = true),
                onSuccess,
                onError: (errors) => notifyError('Bulk Delete Failed', errors),
                onFinish: () => (bulkDeleting.value = false),
            },
        );
    };

    const bulkDownload = async (ids) => {
        if (!ids.length || bulkDownloading.value) return;
        bulkDownloading.value = true;

        try {
            const response = await window.axios.post(
                route('documents.bulk-download'),
                { ids },
                { responseType: 'blob' },
            );

            const url = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `docpad-documents-${Date.now()}.zip`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch {
            notifyError('Bulk Download Failed', { zip: 'Failed to generate ZIP archive.' });
        } finally {
            bulkDownloading.value = false;
        }
    };

    const createFolder = (name, { onSuccess } = {}) => {
        router.post(
            route('document-folders.store'),
            { name },
            {
                preserveScroll: true,
                onStart: () => (managingFolder.value = true),
                onSuccess,
                onError: (errors) => notifyError('Create Folder Failed', errors),
                onFinish: () => (managingFolder.value = false),
            },
        );
    };

    const deleteFolder = (folder, { onSuccess } = {}) => {
        router.delete(route('document-folders.destroy', folder.id), {
            preserveScroll: true,
            onStart: () => (managingFolder.value = true),
            onSuccess,
            onError: (errors) => notifyError('Delete Folder Failed', errors),
            onFinish: () => (managingFolder.value = false),
        });
    };

    const createShareLink = (document, payload, { onSuccess } = {}) => {
        const data = typeof payload === 'string' ? { expiry: payload } : payload;
        router.post(
            route('documents.share.store', document.id),
            data,
            {
                preserveScroll: true,
                onStart: () => (sharing.value = true),
                onSuccess,
                onError: (errors) => notifyError('Share Failed', errors),
                onFinish: () => (sharing.value = false),
            },
        );
    };

    const updateSharePassword = (document, payload = {}, { onSuccess } = {}) => {
        router.patch(
            route('documents.share.password', document.id),
            payload,
            {
                preserveScroll: true,
                onStart: () => (sharing.value = true),
                onSuccess,
                onError: (errors) => notifyError('Update Password Failed', errors),
                onFinish: () => (sharing.value = false),
            },
        );
    };

    const revokeShareLink = (document, payload = {}, { onSuccess } = {}) => {
        router.delete(route('documents.share.destroy', document.id), {
            data: { notify_members: payload?.notify_members ? 1 : 0 },
            preserveScroll: true,
            onStart: () => (sharing.value = true),
            onSuccess,
            onError: (errors) => notifyError('Revoke Failed', errors),
            onFinish: () => (sharing.value = false),
        });
    };

    return {
        uploading,
        creating,
        saving,
        renaming,
        assigningTeams,
        updatingVisibility,
        movingFolder,
        bulkDownloading,
        bulkDeleting,
        managingFolder,
        deleting,
        sharing,
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
    };
}
