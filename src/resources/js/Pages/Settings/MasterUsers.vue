<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/utils/formatters';

const props = defineProps({
    users: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const toast = useToast();
const search = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const editingUser = ref(null);
const deletingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    team_id: null,
    is_superadmin: false,
});

const filteredUsers = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter(
        (u) =>
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            (u.team_name ?? '').toLowerCase().includes(q),
    );
});

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    form.team_id = props.teams[0]?.id ?? null;
    form.is_superadmin = false;
    dialogVisible.value = true;
};

const openEdit = (user) => {
    editingUser.value = user;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.team_id = user.team_id;
    form.is_superadmin = Boolean(user.is_superadmin);
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

    if (editingUser.value) {
        form.put(route('settings.users.update', editingUser.value.id), options);
    } else {
        form.post(route('settings.users.store'), options);
    }
};

const confirmDelete = (user) => {
    deletingUser.value = user;
    deleteDialogVisible.value = true;
};

const deleteUser = () => {
    if (!deletingUser.value) return;
    router.delete(route('settings.users.destroy', deletingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteDialogVisible.value = false),
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Delete Failed',
                detail: Object.values(errors)[0] ?? 'Unable to delete user.',
                life: 4000,
            });
        },
    });
};
</script>

<template>
    <Head title="Master Users" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Banner -->
            <section
                class="rounded-2xl bg-gradient-to-r from-[#dff2e7] to-[#e9f6ee] dark:from-emerald-950/60 dark:to-surface-900 border border-emerald-200/70 dark:border-emerald-900/50 px-6 sm:px-8 py-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-[#1f6f46] text-white shadow-sm shrink-0">
                            <i class="pi pi-user-edit text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0">
                                Master Users
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                                Manage registered users, team assignments, and Superadmin roles.
                            </p>
                        </div>
                    </div>

                    <Button
                        label="Add User"
                        icon="pi pi-user-plus"
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
                        <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">User List</h2>
                    </div>

                    <IconField class="w-full sm:w-72">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Search name, email, or team..." fluid class="!rounded-xl" />
                    </IconField>
                </header>

                <DataTable :value="filteredUsers" dataKey="id" paginator :rows="10">
                    <template #empty>
                        <div class="py-10 text-center text-slate-500 dark:text-surface-400">
                            No users found.
                        </div>
                    </template>

                    <Column field="name" header="Name">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-semibold text-slate-800 dark:text-surface-0">{{ data.name }}</span>
                                <span class="text-xs text-slate-500 dark:text-surface-400">{{ data.email }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Role">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_superadmin ? 'Superadmin' : 'Member'"
                                :severity="data.is_superadmin ? 'warn' : 'info'"
                            />
                        </template>
                    </Column>

                    <Column header="Team">
                        <template #body="{ data }">
                            <span v-if="data.is_superadmin" class="text-xs text-slate-400 italic">All Teams</span>
                            <span v-else-if="data.team_name" class="text-sm font-medium text-slate-700 dark:text-surface-200">
                                {{ data.team_name }}
                            </span>
                            <span v-else class="text-xs text-slate-400">Unassigned</span>
                        </template>
                    </Column>

                    <Column field="created_at" header="Joined">
                        <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
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
                                    v-if="data.id !== currentUserId"
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
            :style="{ width: '30rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-user text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold">{{ editingUser ? 'Edit User' : 'Add User' }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" @submit.prevent="submitForm">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Full Name</label>
                    <InputText v-model="form.name" placeholder="Full name" fluid :invalid="Boolean(form.errors.name)" />
                    <small v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Email</label>
                    <InputText v-model="form.email" type="email" placeholder="user@example.com" fluid :invalid="Boolean(form.errors.email)" />
                    <small v-if="form.errors.email" class="text-red-500 text-xs">{{ form.errors.email }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">
                        Password
                        <span v-if="editingUser" class="text-xs font-normal text-slate-400">(leave blank to keep current)</span>
                    </label>
                    <Password
                        v-model="form.password"
                        :feedback="false"
                        toggle-mask
                        fluid
                        placeholder="Enter password"
                        :invalid="Boolean(form.errors.password)"
                    />
                    <small v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</small>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-semibold">Superadmin Role</span>
                    <ToggleSwitch v-model="form.is_superadmin" />
                </div>

                <div v-if="!form.is_superadmin" class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Team</label>
                    <Select
                        v-model="form.team_id"
                        :options="teams"
                        option-label="name"
                        option-value="id"
                        filter
                        placeholder="Select a team"
                        fluid
                        :invalid="Boolean(form.errors.team_id)"
                    />
                    <small v-if="form.errors.team_id" class="text-red-500 text-xs">{{ form.errors.team_id }}</small>
                </div>
            </form>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="dialogVisible = false" />
                <Button
                    :label="editingUser ? 'Save Changes' : 'Create User'"
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
                    <span class="text-lg font-bold">Delete User</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300">
                Are you sure you want to delete user <span class="font-semibold">{{ deletingUser?.name }}</span>?
            </p>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="deleteDialogVisible = false" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteUser" />
            </template>
        </Dialog>
    </AppLayout>
</template>
