<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    teams: { type: Array, default: () => [] },
});

const toast = useToast();
const search = ref('');
const dialogVisible = ref(false);
const deleteDialogVisible = ref(false);
const editingTeam = ref(null);
const deletingTeam = ref(null);

const form = useForm({
    team_code: '',
    team_name: '',
    max_member: 5,
    description: '',
    is_active: true,
});

const filteredTeams = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.teams;
    return props.teams.filter(
        (t) =>
            t.team_code.toLowerCase().includes(q) ||
            t.team_name.toLowerCase().includes(q) ||
            (t.description ?? '').toLowerCase().includes(q),
    );
});

const openCreate = () => {
    editingTeam.value = null;
    form.reset();
    form.clearErrors();
    form.max_member = 5;
    form.is_active = true;
    dialogVisible.value = true;
};

const openEdit = (team) => {
    editingTeam.value = team;
    form.clearErrors();
    form.team_code = team.team_code;
    form.team_name = team.team_name;
    form.max_member = team.max_member;
    form.description = team.description ?? '';
    form.is_active = Boolean(team.is_active);
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

    if (editingTeam.value) {
        form.put(route('settings.teams.update', editingTeam.value.id), options);
    } else {
        form.post(route('settings.teams.store'), options);
    }
};

const confirmDelete = (team) => {
    deletingTeam.value = team;
    deleteDialogVisible.value = true;
};

const deleteTeam = () => {
    if (!deletingTeam.value) return;
    router.delete(route('settings.teams.destroy', deletingTeam.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteDialogVisible.value = false),
    });
};
</script>

<template>
    <Head title="Master Teams" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header Banner -->
            <section
                class="rounded-2xl bg-gradient-to-r from-[#dff2e7] to-[#e9f6ee] dark:from-emerald-950/60 dark:to-surface-900 border border-emerald-200/70 dark:border-emerald-900/50 px-6 sm:px-8 py-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-[#1f6f46] text-white shadow-sm shrink-0">
                            <i class="pi pi-users text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0">
                                {{ t('settings.master_teams') }}
                            </h1>
                            <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                                Manage workspace teams, registration team codes, and member capacity limits.
                            </p>
                        </div>
                    </div>

                    <Button
                        :label="t('settings.add_team')"
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
                        <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">Team List</h2>
                    </div>

                    <IconField class="w-full sm:w-72">
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="search" placeholder="Search team name or code..." fluid class="!rounded-xl" />
                    </IconField>
                </header>

                <DataTable :value="filteredTeams" dataKey="id" paginator :rows="10">
                    <template #empty>
                        <div class="py-10 text-center text-slate-500 dark:text-surface-400">
                            No teams found.
                        </div>
                    </template>

                    <Column field="team_code" header="Team Code">
                        <template #body="{ data }">
                            <code class="px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-300 font-semibold text-xs">
                                {{ data.team_code }}
                            </code>
                        </template>
                    </Column>

                    <Column field="team_name" header="Team Name">
                        <template #body="{ data }">
                            <span class="font-semibold text-slate-800 dark:text-surface-0">{{ data.team_name }}</span>
                        </template>
                    </Column>

                    <Column header="Members">
                        <template #body="{ data }">
                            <span class="text-sm font-medium text-slate-700 dark:text-surface-200">
                                {{ data.users_count }} / {{ data.max_member > 0 ? data.max_member : '∞' }}
                            </span>
                        </template>
                    </Column>

                    <Column field="description" header="Description">
                        <template #body="{ data }">
                            <span class="text-sm text-slate-500 dark:text-surface-400">
                                {{ data.description || '—' }}
                            </span>
                        </template>
                    </Column>

                    <Column field="is_active" header="Status">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_active ? 'Active' : 'Inactive'"
                                :severity="data.is_active ? 'success' : 'secondary'"
                            />
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" headerClass="!text-right" bodyClass="!text-right">
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
            :style="{ width: '30rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-users text-[#1f6f46] dark:text-emerald-400"></i>
                    <span class="text-lg font-bold">{{ editingTeam ? t('settings.edit_team') : t('settings.add_team') }}</span>
                </div>
            </template>

            <form class="space-y-4 pt-1" @submit.prevent="submitForm">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Team Code</label>
                    <InputText
                        v-model="form.team_code"
                        placeholder="e.g. engineering-team"
                        fluid
                        :invalid="Boolean(form.errors.team_code)"
                    />
                    <small v-if="form.errors.team_code" class="text-red-500 text-xs">{{ form.errors.team_code }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Team Name</label>
                    <InputText
                        v-model="form.team_name"
                        placeholder="e.g. Engineering Team"
                        fluid
                        :invalid="Boolean(form.errors.team_name)"
                    />
                    <small v-if="form.errors.team_name" class="text-red-500 text-xs">{{ form.errors.team_name }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Max Members (0 = Unlimited)</label>
                    <InputNumber v-model="form.max_member" :min="0" :max="10000" fluid />
                    <small v-if="form.errors.max_member" class="text-red-500 text-xs">{{ form.errors.max_member }}</small>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">Description</label>
                    <Textarea v-model="form.description" rows="2" placeholder="Optional description" fluid />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <span class="text-sm font-semibold">Active (Allow Registration)</span>
                    <ToggleSwitch v-model="form.is_active" />
                </div>
            </form>

            <template #footer>
                <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="dialogVisible = false" />
                <Button
                    :label="editingTeam ? t('common.save_changes') : t('settings.add_team')"
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
                    <span class="text-lg font-bold">{{ t('settings.delete_team') }}</span>
                </div>
            </template>

            <p class="text-sm text-slate-600 dark:text-surface-300">
                Are you sure you want to delete team <span class="font-semibold">{{ deletingTeam?.team_name }}</span>?
                All users belonging to this team will also be removed.
            </p>

            <template #footer>
                <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="deleteDialogVisible = false" />
                <Button :label="t('common.delete')" icon="pi pi-trash" severity="danger" @click="deleteTeam" />
            </template>
        </Dialog>
    </AppLayout>
</template>
