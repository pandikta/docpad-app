<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/utils/formatters';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

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
    is_team_leader: false,
    storage_limit_mb: 100,
});

const formatStorageBytes = (bytes) => {
    const value = Number(bytes ?? 0);
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    if (value < 1024 * 1024 * 1024) return `${(value / (1024 * 1024)).toFixed(1)} MB`;
    return `${(value / (1024 * 1024 * 1024)).toFixed(2)} GB`;
};

const storagePercent = (user) => {
    if (!user?.storage_limit_mb) return 0;
    const limitBytes = Number(user.storage_limit_mb) * 1024 * 1024;
    if (limitBytes <= 0) return 0;
    return Math.min(100, Math.round((Number(user.storage_used_bytes ?? 0) / limitBytes) * 100));
};

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
    form.name = '';
    form.email = '';
    form.password = '';
    form.team_id = props.teams[0]?.id ?? null;
    form.is_superadmin = false;
    form.is_team_leader = false;
    form.storage_limit_mb = 100;
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
    form.is_team_leader = Boolean(user.is_team_leader);
    form.storage_limit_mb = user.storage_limit_mb ?? null;
    dialogVisible.value = true;
};

const submitForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            form.reset();
        },
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

// --- Change Password Modal (Superadmin) ---------------------------------
const passwordDialogVisible = ref(false);
const passwordTargetUser = ref(null);
const sendingResetEmail = ref(false);

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
    notify_in_app: true,
    notify_email: true,
});

const passwordRules = computed(() => {
    const val = passwordForm.password ?? '';
    return {
        upper: /[A-Z]/.test(val),
        lower: /[a-z]/.test(val),
        number: /[0-9]/.test(val),
        special: /[^A-Za-z0-9]/.test(val),
        length: val.length >= 8,
    };
});

const passedRuleCount = computed(() => Object.values(passwordRules.value).filter(Boolean).length);
const allPasswordRulesMet = computed(() => passedRuleCount.value === 5);
const passwordMismatch = computed(
    () =>
        passwordForm.password_confirmation.length > 0 &&
        passwordForm.password !== passwordForm.password_confirmation,
);
const canSubmitPassword = computed(
    () =>
        allPasswordRulesMet.value &&
        passwordForm.password_confirmation.length > 0 &&
        !passwordMismatch.value,
);

const openChangePassword = (user) => {
    passwordTargetUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
    passwordForm.notify_in_app = true;
    passwordForm.notify_email = true;
    passwordDialogVisible.value = true;
};

const submitChangePassword = () => {
    if (!passwordTargetUser.value || !canSubmitPassword.value) return;

    passwordForm.patch(route('settings.users.password', passwordTargetUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            passwordDialogVisible.value = false;
            passwordForm.reset();
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Reset Password Failed',
                detail: Object.values(errors)[0] ?? 'Please check the password requirements.',
                life: 4000,
            });
        },
    });
};

const confirmEmailResetVisible = ref(false);

const sendResetByEmail = () => {
    if (!passwordTargetUser.value || sendingResetEmail.value) return;
    sendingResetEmail.value = true;

    router.post(
        route('settings.users.send-reset-link', passwordTargetUser.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmEmailResetVisible.value = false;
                passwordDialogVisible.value = false;
                passwordForm.reset();
            },
            onError: (errors) => {
                toast.add({
                    severity: 'error',
                    summary: 'Send Email Failed',
                    detail: Object.values(errors)[0] ?? 'Could not send reset email.',
                    life: 4000,
                });
            },
            onFinish: () => (sendingResetEmail.value = false),
        },
    );
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
                                {{ t('settings.master_users') }}
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                                Manage registered users, team assignments, and Superadmin roles.
                            </p>
                        </div>
                    </div>

                    <Button
                        :label="t('settings.add_user')"
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
                        <InputText v-model="search" :placeholder="t('settings.search_users')" fluid class="!rounded-xl" />
                    </IconField>
                </header>

                <DataTable :value="filteredUsers" dataKey="id" paginator :rows="10">
                    <template #empty>
                        <div class="py-10 text-center text-slate-500 dark:text-surface-400">
                            No users found.
                        </div>
                    </template>

                    <Column field="name" :header="t('profile.name')">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-semibold text-slate-800 dark:text-surface-0">{{ data.name }}</span>
                                <span class="text-xs text-slate-500 dark:text-surface-400">{{ data.email }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('settings.role')">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_superadmin ? 'Superadmin' : data.is_team_leader ? 'PM / Team Lead' : 'Member'"
                                :severity="data.is_superadmin ? 'warn' : data.is_team_leader ? 'success' : 'info'"
                            />
                        </template>
                    </Column>

                    <Column :header="t('settings.team')">
                        <template #body="{ data }">
                            <span v-if="data.is_superadmin" class="text-xs text-slate-400 italic">All Teams</span>
                            <span v-else-if="data.team_name" class="text-sm font-medium text-slate-700 dark:text-surface-200">
                                {{ data.team_name }}
                            </span>
                            <span v-else class="text-xs text-slate-400">{{ t('table.unassigned') }}</span>
                        </template>
                    </Column>

                    <Column :header="t('settings.storage')">
                        <template #body="{ data }">
                            <div class="flex min-w-[8.5rem] flex-col gap-1">
                                <span class="text-xs font-medium text-slate-700 dark:text-surface-200">
                                    {{ formatStorageBytes(data.storage_used_bytes) }} /
                                    {{ data.storage_limit_mb ? `${data.storage_limit_mb} MB` : t('common.unlimited') }}
                                </span>
                                <div
                                    v-if="data.storage_limit_mb"
                                    class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-surface-700"
                                >
                                    <div
                                        class="h-full rounded-full transition-all"
                                        :class="storagePercent(data) >= 90 ? 'bg-red-500' : 'bg-[#1f6f46]'"
                                        :style="{ width: `${storagePercent(data)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column field="last_login" :header="t('settings.last_login')">
                        <template #body="{ data }">
                            <span v-if="data.last_login">{{ formatDateTime(data.last_login) }}</span>
                            <span v-else class="text-xs text-slate-400 italic">{{ t('common.never') }}</span>
                        </template>
                    </Column>

                    <Column field="created_at" :header="t('settings.joined')">
                        <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
                    </Column>

                    <Column :header="t('common.actions')" headerClass="!text-right" bodyClass="!text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button
                                    v-tooltip.top="'Change Password'"
                                    icon="pi pi-key"
                                    severity="secondary"
                                    variant="outlined"
                                    size="small"
                                    @click="openChangePassword(data)"
                                />
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
                    <span class="text-lg font-bold">{{ editingUser ? t('settings.edit_user') : t('settings.add_user') }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" autocomplete="off" @submit.prevent="submitForm">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Full Name</label>
                    <InputText v-model="form.name" placeholder="Full name" autocomplete="off" fluid :invalid="Boolean(form.errors.name)" />
                    <small v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Email</label>
                    <InputText v-model="form.email" type="email" placeholder="user@example.com" autocomplete="off" fluid :invalid="Boolean(form.errors.email)" />
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
                        :inputProps="{ autocomplete: 'new-password' }"
                        :invalid="Boolean(form.errors.password)"
                    />
                    <small v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</small>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-semibold">Superadmin Role</span>
                    <ToggleSwitch v-model="form.is_superadmin" />
                </div>

                <div v-if="!form.is_superadmin" class="flex items-center justify-between pt-1">
                    <div>
                        <span class="text-sm font-semibold">Team Leader (PM)</span>
                        <p class="text-xs text-slate-400">Can manage team share links & member permissions</p>
                    </div>
                    <ToggleSwitch v-model="form.is_team_leader" />
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

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">
                        Max Storage (MB)
                        <span class="text-xs font-normal text-slate-400">(leave empty for Unlimited)</span>
                    </label>
                    <InputNumber
                        v-model="form.storage_limit_mb"
                        :min="1"
                        :useGrouping="false"
                        placeholder="Unlimited"
                        fluid
                        :invalid="Boolean(form.errors.storage_limit_mb)"
                    />
                    <small v-if="form.errors.storage_limit_mb" class="text-red-500 text-xs">{{ form.errors.storage_limit_mb }}</small>
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

        <!-- Change Password Dialog -->
        <Dialog
            v-model:visible="passwordDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '32rem' }"
            :breakpoints="{ '640px': '94vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-lock text-[#1f6f46] dark:text-emerald-400"></i>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-surface-0 leading-tight">
                            Change Password
                        </h3>
                        <p v-if="passwordTargetUser" class="text-xs text-slate-500 dark:text-surface-400">
                            {{ passwordTargetUser.name }} · {{ passwordTargetUser.email }}
                        </p>
                    </div>
                </div>
            </template>

            <form id="admin-change-password-form" class="space-y-4 pt-1" autocomplete="off" @submit.prevent="submitChangePassword">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        <span class="text-red-500">*</span> New Password
                    </label>
                    <Password
                        v-model="passwordForm.password"
                        :feedback="false"
                        toggle-mask
                        fluid
                        placeholder="Enter new password"
                        inputClass="!rounded-xl"
                        :inputProps="{ autocomplete: 'new-password' }"
                        :invalid="Boolean(passwordForm.errors.password)"
                    />

                    <!-- Strength Bar (3 segments) -->
                    <div class="grid grid-cols-3 gap-1.5 pt-1">
                        <div
                            class="h-1.5 rounded-full transition-colors"
                            :class="
                                passedRuleCount >= 1
                                    ? passedRuleCount <= 2
                                        ? 'bg-red-500'
                                        : passedRuleCount <= 4
                                          ? 'bg-amber-500'
                                          : 'bg-[#1f6f46]'
                                    : 'bg-slate-200 dark:bg-surface-700'
                            "
                        ></div>
                        <div
                            class="h-1.5 rounded-full transition-colors"
                            :class="
                                passedRuleCount >= 3
                                    ? passedRuleCount <= 4
                                        ? 'bg-amber-500'
                                        : 'bg-[#1f6f46]'
                                    : 'bg-slate-200 dark:bg-surface-700'
                            "
                        ></div>
                        <div
                            class="h-1.5 rounded-full transition-colors"
                            :class="passedRuleCount === 5 ? 'bg-[#1f6f46]' : 'bg-slate-200 dark:bg-surface-700'"
                        ></div>
                    </div>

                    <!-- Checklist Rules -->
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 pt-1 text-xs">
                        <span class="inline-flex items-center gap-1" :class="passwordRules.upper ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                            <i :class="passwordRules.upper ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                            Should contain uppercase
                        </span>
                        <span class="inline-flex items-center gap-1" :class="passwordRules.lower ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                            <i :class="passwordRules.lower ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                            Should contain lowercase
                        </span>
                        <span class="inline-flex items-center gap-1" :class="passwordRules.number ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                            <i :class="passwordRules.number ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                            Should contain number
                        </span>
                        <span class="inline-flex items-center gap-1" :class="passwordRules.special ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                            <i :class="passwordRules.special ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                            Should contain special character
                        </span>
                        <span class="inline-flex items-center gap-1" :class="passwordRules.length ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                            <i :class="passwordRules.length ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                            Password must be at least 8 characters
                        </span>
                    </div>

                    <small v-if="passwordForm.errors.password" class="text-red-500 text-xs">
                        {{ passwordForm.errors.password }}
                    </small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        <span class="text-red-500">*</span> Confirm Password
                    </label>
                    <Password
                        v-model="passwordForm.password_confirmation"
                        :feedback="false"
                        toggle-mask
                        fluid
                        placeholder="Re-enter new password"
                        inputClass="!rounded-xl"
                        :inputProps="{ autocomplete: 'new-password' }"
                        :invalid="passwordMismatch || Boolean(passwordForm.errors.password_confirmation)"
                    />
                    <small v-if="passwordMismatch" class="flex items-center gap-1 text-red-500 text-xs">
                        <i class="pi pi-times-circle text-xs"></i>
                        <span>Passwords do not match.</span>
                    </small>
                    <small v-else-if="passwordForm.password_confirmation && !passwordMismatch" class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs">
                        <i class="pi pi-check-circle text-xs"></i>
                        <span>Passwords match.</span>
                    </small>
                </div>

                <!-- Mandatory Self Notifications (Read-only) -->
                <div class="rounded-xl border border-emerald-200/80 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-3 space-y-2">
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-surface-200 opacity-85 cursor-not-allowed select-none">
                        <Checkbox :modelValue="true" binary disabled />
                        <span>Send in-app notification to user</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-surface-200 opacity-85 cursor-not-allowed select-none">
                        <Checkbox :modelValue="true" binary disabled />
                        <span>Send email notification to user</span>
                    </label>
                </div>
            </form>

            <template #footer>
                <div class="flex flex-wrap items-center justify-between gap-2 w-full">
                    <Button
                        type="button"
                        label="Change by Email"
                        icon="pi pi-envelope"
                        severity="secondary"
                        variant="outlined"
                        size="small"
                        :loading="sendingResetEmail"
                        @click="confirmEmailResetVisible = true"
                    />
                    <div class="flex items-center gap-2 ml-auto">
                        <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="passwordDialogVisible = false" />
                        <Button
                            type="submit"
                            form="admin-change-password-form"
                            label="Save Password"
                            icon="pi pi-check"
                            :loading="passwordForm.processing"
                            :disabled="!canSubmitPassword"
                            class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                        />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- Confirm Reset by Email Dialog -->
        <Dialog
            v-model:visible="confirmEmailResetVisible"
            modal
            :draggable="false"
            :style="{ width: '26rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle text-amber-500"></i>
                    <span class="text-lg font-bold">Reset Password by Email</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                Are you sure you want to generate a new random password and send it to
                <span class="font-semibold text-slate-800 dark:text-surface-0">{{ passwordTargetUser?.email }}</span>?
                The user's current password will be replaced.
            </p>

            <template #footer>
                <Button :label="t('common.cancel')" severity="secondary" variant="outlined" :disabled="sendingResetEmail" @click="confirmEmailResetVisible = false" />
                <Button
                    label="Send Random Password"
                    icon="pi pi-send"
                    severity="warn"
                    :loading="sendingResetEmail"
                    @click="sendResetByEmail"
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
