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
    const deleting = ref(false);

    const notifyError = (summary, errors) => {
        toast.add({
            severity: 'error',
            summary,
            detail: Object.values(errors)[0] ?? 'Something went wrong.',
            life: 5000,
        });
    };

    const upload = (files) => {
        router.post(
            route('documents.store'),
            { files },
            {
                forceFormData: true,
                preserveScroll: true,
                onStart: () => (uploading.value = true),
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

    return {
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
        destroy,
        download,
    };
}
