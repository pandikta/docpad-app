<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    menus: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    authRules: { type: Array, default: () => [] },
    availableRoutes: { type: Array, default: () => [] },
});

const toast = useToast();
const scopeMode = ref('team');
const selectedTeamId = ref(props.teams[0]?.id ?? null);
const selectedUserId = ref(props.users[0]?.id ?? null);
const searchQuery = ref('');
const savingKey = ref(null);

// --- Add / Edit / Delete Menu Dialog -------------------------------------
const menuDialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const editingMenu = ref(null);
const deletingMenu = ref(null);

const sectionOptions = [
    { label: 'Main Sidebar', value: 'main' },
    { label: 'Settings Panel', value: 'settings' },
];

const menuForm = useForm({
    parent_id: null,
    code: '',
    name: '',
    group: 'Master Setting',
    section: 'settings',
    route_name: '',
    icon: 'pi pi-circle',
    description: '',
    default_allowed: false,
    sort_order: 50,
});

// Pilihan Parent Menu (hanya menu top-level di sidebar utama, selain dirinya sendiri)
const parentMenuOptions = computed(() =>
    props.menus
        .filter((m) => m.section === 'main' && !m.parent_id && m.id !== editingMenu.value?.id)
        .map((m) => ({ label: m.name, value: m.id })),
);

const getParentName = (parentId) => props.menus.find((m) => m.id === parentId)?.name ?? null;

const openCreateMenu = () => {
    editingMenu.value = null;
    menuForm.reset();
    menuForm.clearErrors();
    menuForm.parent_id = null;
    menuForm.group = 'Master Setting';
    menuForm.section = 'settings';
    menuForm.icon = 'pi pi-circle';
    menuForm.default_allowed = false;
    menuForm.sort_order = 50;
    menuDialogVisible.value = true;
};

const openEditMenu = (menu) => {
    editingMenu.value = menu;
    menuForm.clearErrors();
    menuForm.parent_id = menu.parent_id ?? null;
    menuForm.code = menu.code;
    menuForm.name = menu.name;
    menuForm.group = menu.group;
    menuForm.section = menu.section ?? 'settings';
    menuForm.route_name = menu.route_name ?? '';
    menuForm.icon = menu.icon ?? 'pi pi-circle';
    menuForm.description = menu.description ?? '';
    menuForm.default_allowed = Boolean(menu.default_allowed);
    menuForm.sort_order = menu.sort_order ?? 50;
    menuDialogVisible.value = true;
};

const onParentChange = (parentId) => {
    if (parentId) {
        menuForm.section = 'main';
        menuForm.group = 'Main Navigation';
    }
};

const submitMenuForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => (menuDialogVisible.value = false),
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Save Failed',
                detail: Object.values(errors)[0] ?? 'Please check the form.',
                life: 4000,
            });
        },
    };

    if (editingMenu.value) {
        menuForm.put(route('settings.menu-auth.menus.update', editingMenu.value.id), options);
    } else {
        menuForm.post(route('settings.menu-auth.menus.store'), options);
    }
};

const confirmDeleteMenu = (menu) => {
    deletingMenu.value = menu;
    deleteDialogVisible.value = true;
};

const deleteMenu = () => {
    if (!deletingMenu.value) return;
    router.delete(route('settings.menu-auth.menus.destroy', deletingMenu.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteDialogVisible.value = false),
    });
};

// --- Authorization Logic -------------------------------------------------
const selectedUser = computed(() => props.users.find((u) => u.id === selectedUserId.value) ?? null);

const findRule = (menuId, { teamId = null, userId = null } = {}) => {
    return props.authRules.find(
        (r) =>
            r.menu_id === menuId &&
            (teamId !== null ? r.team_id === teamId : r.user_id === userId),
    );
};

const isAllowedForTarget = (menu) => {
    if (scopeMode.value === 'team') {
        const teamRule = findRule(menu.id, { teamId: selectedTeamId.value });
        return teamRule ? Boolean(teamRule.is_allowed) : Boolean(menu.default_allowed);
    }

    const user = selectedUser.value;
    if (!user) return Boolean(menu.default_allowed);

    const userRule = findRule(menu.id, { userId: user.id });
    if (userRule) return Boolean(userRule.is_allowed);

    const teamRule = user.team_id ? findRule(menu.id, { teamId: user.team_id }) : null;
    if (teamRule) return Boolean(teamRule.is_allowed);

    return Boolean(menu.default_allowed);
};

const hasUserOverride = (menu) => {
    if (scopeMode.value !== 'user' || !selectedUserId.value) return false;
    return Boolean(findRule(menu.id, { userId: selectedUserId.value }));
};

const groupedMenus = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    const filtered = props.menus.filter((menu) => {
        if (!q) return true;
        return (
            menu.name.toLowerCase().includes(q) ||
            menu.code.toLowerCase().includes(q) ||
            (menu.description ?? '').toLowerCase().includes(q) ||
            menu.group.toLowerCase().includes(q)
        );
    });

    const groups = new Map();
    for (const menu of filtered) {
        if (!groups.has(menu.group)) groups.set(menu.group, []);
        groups.get(menu.group).push(menu);
    }

    return Array.from(groups.entries()).map(([title, items]) => ({ title, items }));
});

const togglePermission = (menu, nextValue) => {
    const payload = {
        menu_id: menu.id,
        team_id: scopeMode.value === 'team' ? selectedTeamId.value : null,
        user_id: scopeMode.value === 'user' ? selectedUserId.value : null,
        is_allowed: nextValue,
    };

    savingKey.value = menu.id;
    router.put(route('settings.menu-auth.update'), payload, {
        preserveScroll: true,
        onFinish: () => (savingKey.value = null),
    });
};

const resetUserOverride = (menu) => {
    if (!selectedUserId.value) return;

    savingKey.value = menu.id;
    router.delete(route('settings.menu-auth.reset-user'), {
        data: {
            menu_id: menu.id,
            user_id: selectedUserId.value,
        },
        preserveScroll: true,
        onFinish: () => (savingKey.value = null),
    });
};
</script>

<template>
    <Head :title="t('settings.menu_auth')" />

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
                            <i class="pi pi-sitemap text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0">
                                {{ t('settings.menu_auth') }}
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                                Manage dynamic sidebar/settings menus and control access per team or user.
                            </p>
                        </div>
                    </div>

                    <Button
                        label="Add Menu"
                        icon="pi pi-plus"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                        @click="openCreateMenu"
                    />
                </div>
            </section>

            <!-- Controls Card -->
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
                            Filter Menu
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="searchQuery"
                                placeholder="Search menu name or code..."
                                fluid
                                class="!rounded-xl"
                            />
                        </IconField>
                    </div>
                </div>
            </section>

            <!-- Empty state -->
            <section
                v-if="(scopeMode === 'team' && !selectedTeamId) || (scopeMode === 'user' && !selectedUserId)"
                class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 p-10 text-center text-slate-500 dark:text-surface-400"
            >
                <i class="pi pi-info-circle text-3xl mb-2"></i>
                <p>No {{ scopeMode === 'team' ? 'teams' : 'non-superadmin users' }} available to configure yet.</p>
            </section>

            <!-- Menu Groups -->
            <template v-else>
                <section
                    v-for="group in groupedMenus"
                    :key="group.title"
                    class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm overflow-hidden"
                >
                    <header class="px-6 py-4 border-b border-slate-100 dark:border-surface-800 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <i class="pi pi-list text-[#1f6f46] dark:text-emerald-400"></i>
                            <h2 class="text-base font-bold text-slate-800 dark:text-surface-0">
                                {{ group.title }}
                            </h2>
                        </div>
                        <span class="text-xs text-slate-400 dark:text-surface-500">
                            {{ group.items.length }} menus
                        </span>
                    </header>

                    <div class="divide-y divide-slate-100 dark:divide-surface-800">
                        <div
                            v-for="menu in group.items"
                            :key="menu.id"
                            class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 hover:bg-slate-50/70 dark:hover:bg-surface-800/40 transition-colors"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <i :class="[menu.icon || 'pi pi-circle', 'text-sm text-[#1f6f46] dark:text-emerald-400']"></i>
                                    <span class="font-semibold text-sm text-slate-800 dark:text-surface-0">
                                        {{ menu.name }}
                                    </span>
                                    <code
                                        class="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 dark:bg-surface-800 text-slate-600 dark:text-surface-300"
                                    >
                                        {{ menu.code }}
                                    </code>
                                    <Tag
                                        :value="menu.parent_id ? `Sub-menu of: ${getParentName(menu.parent_id)}` : (menu.section === 'main' ? 'Sidebar' : 'Settings')"
                                        severity="secondary"
                                        class="!text-[11px]"
                                    />
                                    <Tag
                                        v-if="hasUserOverride(menu)"
                                        value="Custom Override"
                                        severity="warn"
                                        class="!text-[11px]"
                                    />
                                </div>
                                <p v-if="menu.description" class="mt-0.5 text-xs text-slate-500 dark:text-surface-400">
                                    {{ menu.description }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="hasUserOverride(menu)"
                                    label="Reset to Team"
                                    size="small"
                                    severity="secondary"
                                    variant="text"
                                    :disabled="savingKey === menu.id"
                                    @click="resetUserOverride(menu)"
                                />

                                <Button
                                    v-tooltip.top="'Edit Menu'"
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    variant="text"
                                    size="small"
                                    @click="openEditMenu(menu)"
                                />

                                <Button
                                    v-tooltip.top="'Delete Menu'"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    variant="text"
                                    size="small"
                                    @click="confirmDeleteMenu(menu)"
                                />

                                <ToggleSwitch
                                    :model-value="isAllowedForTarget(menu)"
                                    :disabled="savingKey === menu.id"
                                    @update:model-value="(val) => togglePermission(menu, val)"
                                />
                            </div>
                        </div>
                    </div>
                </section>
            </template>
        </div>

        <!-- Add / Edit Menu Dialog -->
        <Dialog
            v-model:visible="menuDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '32rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-sitemap text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold">{{ editingMenu ? 'Edit Menu' : 'Add New Menu' }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" @submit.prevent="submitMenuForm">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Menu Name</label>
                        <InputText v-model="menuForm.name" placeholder="e.g. Reports" fluid :invalid="Boolean(menuForm.errors.name)" />
                        <small v-if="menuForm.errors.name" class="text-red-500 text-xs">{{ menuForm.errors.name }}</small>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Menu Code</label>
                        <InputText v-model="menuForm.code" placeholder="e.g. menu.reports" fluid :invalid="Boolean(menuForm.errors.code)" />
                        <small v-if="menuForm.errors.code" class="text-red-500 text-xs">{{ menuForm.errors.code }}</small>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">
                        Parent Menu <span class="text-xs font-normal text-slate-400">(optional — attach under another menu)</span>
                    </label>
                    <Select
                        v-model="menuForm.parent_id"
                        :options="parentMenuOptions"
                        option-label="label"
                        option-value="value"
                        show-clear
                        placeholder="None (Top-level / Settings item)"
                        fluid
                        @update:model-value="onParentChange"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Section</label>
                        <Select
                            v-model="menuForm.section"
                            :options="sectionOptions"
                            option-label="label"
                            option-value="value"
                            :disabled="Boolean(menuForm.parent_id)"
                            fluid
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Group</label>
                        <InputText v-model="menuForm.group" placeholder="e.g. Master Setting" fluid :invalid="Boolean(menuForm.errors.group)" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Laravel Route Name</label>
                        <Select
                            v-model="menuForm.route_name"
                            :options="availableRoutes"
                            option-label="label"
                            option-value="value"
                            editable
                            filter
                            show-clear
                            placeholder="Select route (e.g. dashboard)"
                            fluid
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">PrimeIcon Class</label>
                        <InputText v-model="menuForm.icon" placeholder="e.g. pi pi-chart-bar" fluid />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                    <div class="sm:col-span-2 flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Description</label>
                        <InputText v-model="menuForm.description" placeholder="Short description" fluid />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold">Sort Order</label>
                        <InputNumber v-model="menuForm.sort_order" :min="0" :max="9999" fluid />
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-semibold">Allowed by Default for New/All Users</span>
                    <ToggleSwitch v-model="menuForm.default_allowed" />
                </div>
            </form>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="menuDialogVisible = false" />
                <Button
                    :label="editingMenu ? 'Save Changes' : 'Create Menu'"
                    icon="pi pi-check"
                    :loading="menuForm.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    @click="submitMenuForm"
                />
            </template>
        </Dialog>

        <!-- Delete Menu Dialog -->
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
                    <span class="text-lg font-bold">Delete Menu</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300">
                Are you sure you want to remove <span class="font-semibold">{{ deletingMenu?.name }}</span> from the sidebar/settings menu?
            </p>

            <template #footer>
                <Button label="Cancel" severity="secondary" variant="outlined" @click="deleteDialogVisible = false" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteMenu" />
            </template>
        </Dialog>
    </AppLayout>
</template>
