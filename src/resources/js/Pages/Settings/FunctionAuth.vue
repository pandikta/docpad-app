<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    functions: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    authRules: { type: Array, default: () => [] },
});

const toast = useToast();
const scopeMode = ref('team');
const selectedTeamId = ref(props.teams[0]?.id ?? null);
const selectedUserId = ref(props.users[0]?.id ?? null);
const searchQuery = ref('');
const savingKey = ref(null);

// --- Add / Edit / Delete Function Dialog ---------------------------------
const fnDialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const editingFn = ref(null);
const deletingFn = ref(null);

const fnForm = useForm({
    code: '',
    name: '',
    group: 'Documents',
    description: '',
    default_allowed: true,
});

const openCreateFunction = () => {
    editingFn.value = null;
    fnForm.reset();
    fnForm.clearErrors();
    fnForm.group = 'Documents';
    fnForm.default_allowed = true;
    fnDialogVisible.value = true;
};

const openEditFunction = (fn) => {
    editingFn.value = fn;
    fnForm.clearErrors();
    fnForm.code = fn.code;
    fnForm.name = fn.name;
    fnForm.group = fn.group;
    fnForm.description = fn.description ?? '';
    fnForm.default_allowed = Boolean(fn.default_allowed);
    fnDialogVisible.value = true;
};

const submitFunctionForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => (fnDialogVisible.value = false),
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Save Failed',
                detail: Object.values(errors)[0] ?? 'Please check the form.',
                life: 4000,
            });
        },
    };

    if (editingFn.value) {
        fnForm.put(route('settings.function-auth.functions.update', editingFn.value.id), options);
    } else {
        fnForm.post(route('settings.function-auth.functions.store'), options);
    }
};

const confirmDeleteFunction = (fn) => {
    deletingFn.value = fn;
    deleteDialogVisible.value = true;
};

const deleteFunction = () => {
    if (!deletingFn.value) return;
    router.delete(route('settings.function-auth.functions.destroy', deletingFn.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteDialogVisible.value = false),
    });
};

// --- Authorization Logic -------------------------------------------------
const selectedUser = computed(() => props.users.find((u) => u.id === selectedUserId.value) ?? null);

const findRule = (functionId, { teamId = null, userId = null } = {}) => {
    return props.authRules.find(
        (r) =>
            r.function_id === functionId &&
            (teamId !== null ? r.team_id === teamId : r.user_id === userId),
    );
};

const isAllowedForTarget = (fn) => {
    if (scopeMode.value === 'team') {
        const teamRule = findRule(fn.id, { teamId: selectedTeamId.value });
        return teamRule ? Boolean(teamRule.is_allowed) : Boolean(fn.default_allowed);
    }

    const user = selectedUser.value;
    if (!user) return Boolean(fn.default_allowed);

    const userRule = findRule(fn.id, { userId: user.id });
    if (userRule) return Boolean(userRule.is_allowed);

    const teamRule = user.team_id ? findRule(fn.id, { teamId: user.team_id }) : null;
    if (teamRule) return Boolean(teamRule.is_allowed);

    return Boolean(fn.default_allowed);
};

const hasUserOverride = (fn) => {
    if (scopeMode.value !== 'user' || !selectedUserId.value) return false;
    return Boolean(findRule(fn.id, { userId: selectedUserId.value }));
};

const groupedFunctions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const filtered = props.functions.filter((fn) => {
        if (!q) return true;
        return (
            fn.name.toLowerCase().includes(q) ||
            fn.code.toLowerCase().includes(q) ||
            (fn.description ?? '').toLowerCase().includes(q) ||
            fn.group.toLowerCase().includes(q)
        );
    });

    const groups = new Map();
    for (const fn of filtered) {
        if (!groups.has(fn.group)) groups.set(fn.group, []);
        groups.get(fn.group).push(fn);
    }

    return Array.from(groups.entries()).map(([title, items]) => ({ title, items }));
});

const togglePermission = (fn, nextValue) => {
    const payload = {
        function_id: fn.id,
        team_id: scopeMode.value === 'team' ? selectedTeamId.value : null,
        user_id: scopeMode.value === 'user' ? selectedUserId.value : null,
        is_allowed: nextValue,
    };

    savingKey.value = fn.id;
    router.put(route('settings.function-auth.update'), payload, {
        preserveScroll: true,
        onFinish: () => (savingKey.value = null),
    });
};

const resetUserOverride = (fn) => {
    if (!selectedUserId.value) return;

    savingKey.value = fn.id;
    router.delete(route('settings.function-auth.reset-user'), {
        data: {
            function_id: fn.id,
            user_id: selectedUserId.value,
        },
        preserveScroll: true,
        onFinish: () => (savingKey.value = null),
    });
};
</script>

<template>
    <Head title="Function Authorization" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Banner -->
            <section
                class="rounded-2xl bg-gradient-to-r from-[#dff2e7] to-[#e9f6ee] dark:from-emerald-950/60 dark:to-surface-900 border border-emerald-200/70 dark:border-emerald-900/50 px-6 sm:px-8 py-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex items-center justify-center w-12 h-12 rounded-2xl bg-[#1f6f46] text-white shadow-sm shrink-0"
                        >
                            <i class="pi pi-shield text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0">
                                Function Authorization
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                                Register function permissions and control which actions each team or user can perform.
                            </p>
                        </div>
                    </div>

                    <Button
                        label="Add Function"
                        icon="pi pi-plus"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                        @click="openCreateFunction"
                    />
                </div>
            </section>

            <!-- Controls Card: Scope Selector + Search -->
            <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm p-5 sm:p-6">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-surface-400">
                            Scope
                        </span>
                        <div class="inline-flex rounded-xl bg-slate-100 dark:bg-surface-800 p-1">
                            <button
                                type="button"
                                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors cursor-pointer"
                                :class="
                                    scopeMode === 'team'
                                        ? 'bg-white dark:bg-surface-900 text-[#1f6f46] dark:text-emerald-400 shadow-sm'
                                        : 'text-slate-600 dark:text-surface-300 hover:text-slate-900'
                                "
                                @click="scopeMode = 'team'"
                            >
                                <i class="pi pi-users text-xs"></i>
                                <span>By Team</span>
                            </button>
                            <button
                                type="button"
                                class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors cursor-pointer"
                                :class="
                                    scopeMode === 'user'
                                        ? 'bg-white dark:bg-surface-900 text-[#1f6f46] dark:text-emerald-400 shadow-sm'
                                        : 'text-slate-600 dark:text-surface-300 hover:text-slate-900'
                                "
                                @click="scopeMode = 'user'"
                            >
                                <i class="pi pi-user text-xs"></i>
                                <span>By User (Override)</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 min-w-[14rem] max-w-sm flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-surface-400">
                            {{ scopeMode === 'team' ? 'Select Team' : 'Select User' }}
                        </label>
                        <Select
                            v-if="scopeMode === 'team'"
                            v-model="selectedTeamId"
                            :options="teams"
                            option-label="name"
                            option-value="id"
                            filter
                            placeholder="Choose a team"
                            fluid
                            class="!rounded-xl"
                        />
                        <Select
                            v-else
                            v-model="selectedUserId"
                            :options="users"
                            option-label="name"
                            option-value="id"
                            filter
                            placeholder="Choose a user"
                            fluid
                            class="!rounded-xl"
                        >
                            <template #option="{ option }">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ option.name }}</span>
                                    <span class="text-xs text-slate-400">
                                        {{ option.email }} {{ option.team_name ? `· ${option.team_name}` : '' }}
                                    </span>
                                </div>
                            </template>
                        </Select>
                    </div>

                    <div class="flex-1 min-w-[14rem] flex flex-col gap-1.5">
                        <label class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-surface-400">
                            Filter Function
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search permission name or code..."
                                fluid
                                class="!rounded-xl"
                            />
                        </IconField>
                    </div>
                </div>
            </section>

            <!-- Empty state if no target selected -->
            <section
                v-if="(scopeMode === 'team' && !selectedTeamId) || (scopeMode === 'user' && !selectedUserId)"
                class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 p-10 text-center text-slate-500 dark:text-surface-400"
            >
                <i class="pi pi-info-circle text-3xl mb-2"></i>
                <p>No {{ scopeMode === 'team' ? 'teams' : 'non-superadmin users' }} available to configure yet.</p>
            </section>

            <!-- Permission Groups -->
            <template v-else>
                <section
                    v-for="group in groupedFunctions"
                    :key="group.title"
                    class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm overflow-hidden"
                >
                    <header class="px-6 py-4 border-b border-slate-100 dark:border-surface-800 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <i class="pi pi-folder-open text-[#1f6f46] dark:text-emerald-400"></i>
                            <h2 class="text-base font-bold text-slate-800 dark:text-surface-0">
                                {{ group.title }}
                            </h2>
                        </div>
                        <span class="text-xs text-slate-400 dark:text-surface-500">
                            {{ group.items.length }} functions
                        </span>
                    </header>

                    <div class="divide-y divide-slate-100 dark:divide-surface-800">
                        <div
                            v-for="fn in group.items"
                            :key="fn.id"
                            class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-slate-50/70 dark:hover:bg-surface-800/40 transition-colors"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-sm text-slate-800 dark:text-surface-0">
                                        {{ fn.name }}
                                    </span>
                                    <code
                                        class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-surface-800 text-slate-600 dark:text-surface-300"
                                    >
                                        {{ fn.code }}
                                    </code>
                                    <Tag
                                        v-if="hasUserOverride(fn)"
                                        value="Custom Override"
                                        severity="warn"
                                        class="!text-[11px]"
                                    />
                                </div>
                                <p v-if="fn.description" class="mt-0.5 text-xs text-slate-500 dark:text-surface-400">
                                    {{ fn.description }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="hasUserOverride(fn)"
                                    label="Reset to Team"
                                    size="small"
                                    severity="secondary"
                                    variant="text"
                                    :disabled="savingKey === fn.id"
                                    @click="resetUserOverride(fn)"
                                />

                                <Button
                                    v-tooltip.top="'Edit Function'"
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    variant="text"
                                    size="small"
                                    @click="openEditFunction(fn)"
                                />

                                <Button
                                    v-tooltip.top="'Delete Function'"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    variant="text"
                                    size="small"
                                    @click="confirmDeleteFunction(fn)"
                                />

                                <ToggleSwitch
                                    :model-value="isAllowedForTarget(fn)"
                                    :disabled="savingKey === fn.id"
                                    @update:model-value="(val) => togglePermission(fn, val)"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </template>
        </div>

        <!-- Add / Edit Function Dialog -->
        <Dialog
            v-model:visible="fnDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '30rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-shield text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold">{{ editingFn ? 'Edit Function' : 'Add Function' }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" @submit.prevent="submitFunctionForm">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Function Name</label>
                    <InputText v-model="fnForm.name" placeholder="e.g. Export PDF" fluid :invalid="Boolean(fnForm.errors.name)" />
                    <small v-if="fnForm.errors.name" class="text-red-500 text-xs">{{ fnForm.errors.name }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Function Code</label>
                    <InputText v-model="fnForm.code" placeholder="e.g. documents.export" fluid :invalid="Boolean(fnForm.errors.code)" />
                    <small v-if="fnForm.errors.code" class="text-red-500 text-xs">{{ fnForm.errors.code }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Group</label>
                    <InputText v-model="fnForm.group" placeholder="e.g. Documents" fluid :invalid="Boolean(fnForm.errors.group)" />
                    <small v-if="fnForm.errors.group" class="text-red-500 text-xs">{{ fnForm.errors.group }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Description</label>
                    <InputText v-model="fnForm.description" placeholder="Optional description" fluid />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-semibold">Allowed by Default</span>
                    <ToggleSwitch v-model="fnForm.default_allowed" />
                </div>
            </form>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="fnDialogVisible = false" />
                <Button
                    :label="editingFn ? 'Save Changes' : 'Create Function'"
                    icon="pi pi-check"
                    :loading="fnForm.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    @click="submitFunctionForm"
                />
            </template>
        </Dialog>

        <!-- Delete Function Dialog -->
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
                    <span class="text-lg font-bold">Delete Function</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300">
                Are you sure you want to delete function <span class="font-semibold">{{ deletingFn?.name }}</span>
                (<code>{{ deletingFn?.code }}</code>)?
            </p>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="deleteDialogVisible = false" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteFunction" />
            </template>
        </Dialog>
    </AppLayout>
</template>
