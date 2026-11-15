<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    extensions: { type: Array, default: () => [] },
});

const toast = useToast();
const search = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const editingItem = ref(null);
const deletingItem = ref(null);

const form = useForm({
    extension: '',
    label: '',
    description: '',
    is_active: true,
});

const filteredExtensions = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.extensions;
    return props.extensions.filter(
        (ext) =>
            ext.extension.toLowerCase().includes(q) ||
            ext.label.toLowerCase().includes(q) ||
            (ext.description ?? '').toLowerCase().includes(q),
    );
});

const openCreate = () => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    dialogVisible.value = true;
};

const openEdit = (item) => {
    editingItem.value = item;
    form.clearErrors();
    form.extension = item.extension;
    form.label = item.label;
    form.description = item.description ?? '';
    form.is_active = Boolean(item.is_active);
    dialogVisible.value = true;
};

const submitForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialogVisible.value = false),
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Save Failed',
                detail: Object.values(errors)[0] ?? 'Please check the form.',
                life: 4000,
            });
        },
    };

    if (editingItem.value) {
        form.put(route('settings.extensions.update', editingItem.value.id), options);
    } else {
        form.post(route('settings.extensions.store'), options);
    }
};

const toggleActive = (item, value) => {
    router.put(
        route('settings.extensions.update', item.id),
        {
            extension: item.extension,
            label: item.label,
            description: item.description,
            is_active: value,
        },
        { preserveScroll: true },
    );
};

const confirmDelete = (item) => {
    deletingItem.value = item;
    deleteDialogVisible.value = true;
};

const deleteItem = () => {
    if (!deletingItem.value) return;
    router.delete(route('settings.extensions.destroy', deletingItem.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteDialogVisible.value = false),
    });
};
</script>

<template>
    <Head title="Master Extensions" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Banner -->
            <section
                class="rounded-2xl bg-gradient-to-r from-[#dff2e7] to-[#e9f6ee] dark:from-emerald-950/60 dark:to-surface-900 border border-emerald-200/70 dark:border-emerald-900/50 px-6 sm:px-8 py-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-[#1f6f46] text-white shadow-sm shrink-0">
                            <i class="pi pi-file-check text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0">
                                Master Extensions
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                                Manage allowed file extensions for Create, Upload, and Rename document actions.
                            </p>
                        </div>
                    </div>

                    <Button
                        label="Add Extension"
                        icon="pi pi-plus"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                        @click="openCreate"
                    />
                </div>
            </section>

            <!-- Table -->
            <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 px-6 py-5 border-b border-slate-100 dark:border-surface-800">
                    <div class="flex items-center gap-2.5">
                        <i class="pi pi-list text-[#1f6f46] dark:text-emerald-400"></i>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">Extension List</h2>
                    </div>

                    <IconField class="w-full sm:w-72">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Search extension..." fluid class="!rounded-xl" />
                    </IconField>
                </header>

                <DataTable :value="filteredExtensions" dataKey="id" paginator :rows="10">
                    <template #empty>
                        <div class="py-10 text-center text-slate-500 dark:text-surface-400">
                            No extensions found.
                        </div>
                    </template>

                    <Column field="extension" header="Extension">
                        <template #body="{ data }">
                            <code class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-300 font-semibold text-sm">
                                .{{ data.extension }}
                            </code>
                        </template>
                    </Column>

                    <Column field="label" header="Label" />

                    <Column field="description" header="Description">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-500 dark:text-surface-400">
                                {{ data.description || '—' }}
                            </span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2.5">
                                <ToggleSwitch
                                    :model-value="Boolean(data.is_active)"
                                    @update:model-value="(val) => toggleActive(data, val)"
                                />
                                <Tag
                                    :value="data.is_active ? 'Active' : 'Inactive'"
                                    :severity="data.is_active ? 'success' : 'secondary'"
                                />
                            </div>
                        </template>
                    </Column>

                    <Column header="Actions" headerClass="!text-right" bodyClass="!text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button
                                    v-tooltip.top="'Edit'"
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    variant="outlined"
                                    size="small"
                                    @click="openEdit(data)"
                                />
                                <Button
                                    v-tooltip.top="'Delete'"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    variant="outlined"
                                    size="small"
                                    @click="confirmDelete(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </section>
        </div>

        <!-- Add / Edit Dialog -->
        <Dialog
            v-model:visible="dialogVisible"
            modal
            :draggable="false"
            :style="{ width: '28rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-file-check text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold">{{ editingItem ? 'Edit Extension' : 'Add Extension' }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" @submit.prevent="submitForm">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Extension Code</label>
                    <InputText
                        v-model="form.extension"
                        placeholder="e.g. txt, md, log"
                        fluid
                        :invalid="Boolean(form.errors.extension)"
                    />
                    <small v-if="form.errors.extension" class="text-red-500 text-xs">{{ form.errors.extension }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Label</label>
                    <InputText
                        v-model="form.label"
                        placeholder="e.g. Plain Text"
                        fluid
                        :invalid="Boolean(form.errors.label)"
                    />
                    <small v-if="form.errors.label" class="text-red-500 text-xs">{{ form.errors.label }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Description</label>
                    <InputText v-model="form.description" placeholder="Optional description" fluid />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-semibold">Active</span>
                    <ToggleSwitch v-model="form.is_active" />
                </div>
            </form>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="dialogVisible = false" />
                <Button
                    :label="editingItem ? 'Save Changes' : 'Create Extension'"
                    icon="pi pi-check"
                    :loading="form.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    @click="submitForm"
                />
            </template>
        </Dialog>

        <!-- Delete Dialog -->
        <Dialog
            v-model:visible="deleteDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '25rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle text-red-500"></i>
                    <span class="text-lg font-bold">Delete Extension</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300">
                Are you sure you want to delete extension <span class="font-semibold">.{{ deletingItem?.extension }}</span>?
            </p>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="deleteDialogVisible = false" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteItem" />
            </template>
        </Dialog>
    </AppLayout>
</template>
