<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/utils/formatters';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    teamName: { type: String, default: 'My Team' },
    shareLinks: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    functions: { type: Array, default: () => [] },
    teamRules: { type: Array, default: () => [] },
    userRules: { type: Array, default: () => [] },
    joinRequests: { type: Array, default: () => [] },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id ?? null);

const activeTab = ref('links');
const selectedTeamId = ref('all');
const selectedMemberId = ref(props.members[0]?.id ?? null);
const busyKey = ref(null);
const copiedId = ref(null);

const takeOutConfirmVisible = ref(false);
const takingOut = ref(false);

const confirmTakeOut = () => {
    if (!selectedMember.value) return;
    takingOut.value = true;
    router.delete(route('team-control.members.take-out', selectedMember.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            takeOutConfirmVisible.value = false;
        },
        onFinish: () => {
            takingOut.value = false;
        },
    });
};

const pendingJoinCount = computed(
    () => props.joinRequests.filter((r) => r.status === 'pending').length,
);

const joinStatusSeverity = (status) => {
    if (status === 'approved') return 'success';
    if (status === 'rejected') return 'danger';
    if (status === 'cancelled') return 'secondary';
    return 'warn';
};

const approveJoinRequest = (req) => {
    busyKey.value = `approve-join-${req.id}`;
    router.post(
        route('team-control.join-requests.approve', req.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => (busyKey.value = null),
        },
    );
};

const rejectDialogVisible = ref(false);
const rejectingJoin = ref(null);
const rejectForm = useForm({
    rejection_reason: '',
});

const openRejectJoin = (req) => {
    rejectingJoin.value = req;
    rejectForm.reset();
    rejectForm.clearErrors();
    rejectDialogVisible.value = true;
};

const submitRejectJoin = () => {
    if (!rejectingJoin.value) return;
    rejectForm.post(route('team-control.join-requests.reject', rejectingJoin.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogVisible.value = false;
            rejectingJoin.value = null;
        },
    });
};

const teamOptions = computed(() => {
    const map = new Map();
    for (const member of props.members) {
        if (member.team_id && member.team_name && !map.has(member.team_id)) {
            map.set(member.team_id, member.team_name);
        }
    }
    return [
        { label: 'All Teams', value: 'all' },
        ...Array.from(map.entries()).map(([id, name]) => ({ label: name, value: id })),
    ];
});

const filteredMembers = computed(() => {
    if (selectedTeamId.value === 'all') return props.members;
    return props.members.filter((m) => Number(m.team_id) === Number(selectedTeamId.value));
});

const memberOptions = computed(() =>
    filteredMembers.value.map((m) => ({
        id: m.id,
        label: `${m.name} (${m.email})`,
    })),
);

const selectedMember = computed(
    () => filteredMembers.value.find((m) => Number(m.id) === Number(selectedMemberId.value)) ?? null,
);

watch(filteredMembers, (list) => {
    if (!list.some((m) => Number(m.id) === Number(selectedMemberId.value))) {
        selectedMemberId.value = list[0]?.id ?? null;
    }
});

const copyLink = async (link) => {
    try {
        await navigator.clipboard.writeText(link.url);
        copiedId.value = link.id;
        setTimeout(() => {
            if (copiedId.value === link.id) copiedId.value = null;
        }, 2000);
    } catch {
        copiedId.value = null;
    }
};

const revokeLink = (link) => {
    busyKey.value = `revoke-${link.id}`;
    router.delete(route('team-control.share-links.revoke', link.id), {
        preserveScroll: true,
        onFinish: () => (busyKey.value = null),
    });
};

const userRuleMap = computed(() => {
    const map = new Map();
    for (const rule of props.userRules) {
        map.set(`${rule.user_id}:${rule.function_id}`, Boolean(rule.is_allowed));
    }
    return map;
});

const teamRuleMap = computed(() => {
    const map = new Map();
    for (const rule of props.teamRules) {
        map.set(`${rule.team_id}:${rule.function_id}`, Boolean(rule.is_allowed));
    }
    return map;
});

const hasUserOverride = (member, fn) => userRuleMap.value.has(`${member.id}:${fn.id}`);

const isEffectiveAllowed = (member, fn) => {
    const userKey = `${member.id}:${fn.id}`;
    if (userRuleMap.value.has(userKey)) {
        return userRuleMap.value.get(userKey);
    }

    const teamKey = `${member.team_id}:${fn.id}`;
    if (member.team_id && teamRuleMap.value.has(teamKey)) {
        return teamRuleMap.value.get(teamKey);
    }

    return Boolean(fn.default_allowed);
};

const togglePermission = (member, fn, newValue) => {
    busyKey.value = `perm-${member.id}-${fn.id}`;
    router.put(
        route('team-control.permissions.update'),
        {
            user_id: member.id,
            function_id: fn.id,
            is_allowed: Boolean(newValue),
        },
        {
            preserveScroll: true,
            onFinish: () => (busyKey.value = null),
        },
    );
};

const resetPermission = (member, fn) => {
    busyKey.value = `reset-${member.id}-${fn.id}`;
    router.delete(route('team-control.permissions.reset'), {
        data: {
            user_id: member.id,
            function_id: fn.id,
        },
        preserveScroll: true,
        onFinish: () => (busyKey.value = null),
    });
};
</script>

<template>
    <Head :title="t('team_control.page_title')" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <i class="pi pi-shield text-2xl text-[#1f6f46] dark:text-emerald-400"></i>
                        <h1 class="text-2xl font-bold text-slate-800 dark:text-surface-0">{{ t('team_control.page_title') }}</h1>
                        <Tag :value="teamName" severity="success" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-surface-400">
                        {{ t('team_control.subtitle') }}
                    </p>
                </div>

                <Link :href="route('dashboard')">
                    <Button :label="t('common.back_to_dashboard')" icon="pi pi-arrow-left" severity="secondary" variant="outlined" />
                </Link>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-colors cursor-pointer"
                    :class="
                        activeTab === 'links'
                            ? 'border-[#1f6f46] bg-[#1f6f46] text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-[#1f6f46] dark:border-surface-700 dark:bg-surface-900 dark:text-surface-300'
                    "
                    @click="activeTab = 'links'"
                >
                    <i class="pi pi-link text-xs"></i>
                    <span>{{ t('team_control.tab_links', { count: shareLinks.length }) }}</span>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-colors cursor-pointer"
                    :class="
                        activeTab === 'permissions'
                            ? 'border-[#1f6f46] bg-[#1f6f46] text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-[#1f6f46] dark:border-surface-700 dark:bg-surface-900 dark:text-surface-300'
                    "
                    @click="activeTab = 'permissions'"
                >
                    <i class="pi pi-users text-xs"></i>
                    <span>{{ t('team_control.tab_permissions', { count: members.length }) }}</span>
                </button>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition-colors cursor-pointer"
                    :class="
                        activeTab === 'join_requests'
                            ? 'border-[#1f6f46] bg-[#1f6f46] text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-[#1f6f46] dark:border-surface-700 dark:bg-surface-900 dark:text-surface-300'
                    "
                    @click="activeTab = 'join_requests'"
                >
                    <i class="pi pi-user-plus text-xs"></i>
                    <span>{{ t('team_control.tab_join_requests', { count: pendingJoinCount }) }}</span>
                </button>
            </div>

            <!-- Tab 1: Active Share Links (Point 3) -->
            <section
                v-if="activeTab === 'links'"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-surface-800 dark:bg-surface-900"
            >
                <DataTable :value="shareLinks" dataKey="id" paginator :rows="10">
                    <template #empty>
                        <div class="py-12 text-center text-slate-500 dark:text-surface-400">
                            <i class="pi pi-link-slash mb-2 text-3xl"></i>
                            <p>{{ t('team_control.empty_links') }}</p>
                        </div>
                    </template>

                    <Column field="document_name" :header="t('team_control.col_document')">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-semibold text-slate-800 dark:text-surface-0">{{ data.document_name }}</span>
                                <span class="truncate max-w-xs font-mono text-xs text-slate-400">{{ data.url }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="creator_name" :header="t('team_control.col_shared_by')" />

                    <Column field="expires_at" :header="t('team_control.col_expires')">
                        <template #body="{ data }">
                            <span v-if="data.expires_at" class="text-sm">{{ formatDateTime(data.expires_at) }}</span>
                            <Tag v-else :value="t('team_control.never_expires')" severity="secondary" />
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" headerClass="!text-right" bodyClass="!text-right">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-2">
                                <Button
                                    v-tooltip.top="'Copy Link'"
                                    :icon="copiedId === data.id ? 'pi pi-check' : 'pi pi-copy'"
                                    :severity="copiedId === data.id ? 'success' : 'secondary'"
                                    variant="outlined"
                                    size="small"
                                    @click="copyLink(data)"
                                />
                                <Button
                                    v-tooltip.top="'Open Link'"
                                    icon="pi pi-external-link"
                                    severity="secondary"
                                    variant="outlined"
                                    size="small"
                                    as="a"
                                    :href="data.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                />
                                <Button
                                    :label="t('team_control.revoke')"
                                    icon="pi pi-ban"
                                    severity="danger"
                                    variant="outlined"
                                    size="small"
                                    :loading="busyKey === `revoke-${data.id}`"
                                    @click="revokeLink(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </section>

            <!-- Tab 2: Member Permissions (Point 4) -->
            <section v-else-if="activeTab === 'permissions'" class="space-y-4">
                <div
                    v-if="!members.length"
                    class="rounded-2xl border border-slate-200 bg-white py-12 text-center text-slate-500 shadow-sm dark:border-surface-800 dark:bg-surface-900 dark:text-surface-400"
                >
                    <i class="pi pi-users mb-2 text-3xl"></i>
                    <p>{{ t('team_control.no_members') }}</p>
                </div>

                <template v-else>
                    <!-- Team & Member Dropdown Selector -->
                    <div class="flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-surface-800 dark:bg-surface-900">
                        <div v-if="teamOptions.length > 2" class="flex w-full flex-col gap-1.5 sm:w-56">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-surface-400">
                                {{ t('team_control.team_label') }}
                            </label>
                            <Select
                                v-model="selectedTeamId"
                                :options="teamOptions"
                                optionLabel="label"
                                optionValue="value"
                                fluid
                            />
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-surface-400">
                                {{ t('team_control.select_member') }}
                            </label>
                            <Select
                                v-model="selectedMemberId"
                                :options="memberOptions"
                                optionLabel="label"
                                optionValue="id"
                                filter
                                :placeholder="t('team_control.choose_member')"
                                fluid
                            />
                        </div>
                    </div>

                    <!-- Selected Member Permissions Card -->
                    <div
                        v-if="selectedMember"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-surface-800 dark:bg-surface-900"
                    >
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 dark:border-surface-800">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-bold text-slate-800 dark:text-surface-0">{{ selectedMember.name }}</span>
                                    <Tag v-if="selectedMember.is_team_leader" value="PM / Team Lead" severity="success" />
                                    <Tag v-if="selectedMember.team_name" :value="selectedMember.team_name" severity="secondary" />
                                </div>
                                <p class="text-xs text-slate-500 dark:text-surface-400">{{ selectedMember.email }}</p>
                            </div>

                            <Button
                                v-if="selectedMember.team_id && Number(selectedMember.id) !== Number(currentUserId)"
                                :label="t('team_control.take_out_button')"
                                icon="pi pi-user-minus"
                                severity="danger"
                                variant="outlined"
                                size="small"
                                class="!rounded-xl"
                                @click="takeOutConfirmVisible = true"
                            />
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="fn in functions"
                                :key="fn.id"
                                class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-3.5 py-2.5 dark:border-surface-800"
                            >
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="truncate text-sm font-semibold text-slate-700 dark:text-surface-100">
                                            {{ fn.name }}
                                        </span>
                                        <span
                                            v-if="hasUserOverride(selectedMember, fn)"
                                            class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                                        >
                                            {{ t('team_control.custom') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="truncate text-[11px] text-slate-400">{{ fn.code }}</span>
                                        <button
                                            v-if="hasUserOverride(selectedMember, fn)"
                                            type="button"
                                            class="text-[11px] font-semibold text-[#1f6f46] hover:underline dark:text-emerald-400 cursor-pointer"
                                            :disabled="busyKey === `reset-${selectedMember.id}-${fn.id}`"
                                            @click="resetPermission(selectedMember, fn)"
                                        >
                                            {{ t('common.reset') }}
                                        </button>
                                    </div>
                                </div>

                                <ToggleSwitch
                                    :modelValue="isEffectiveAllowed(selectedMember, fn)"
                                    :disabled="busyKey === `perm-${selectedMember.id}-${fn.id}`"
                                    @update:modelValue="(val) => togglePermission(selectedMember, fn, val)"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </section>

            <!-- Tab 3: Join Team Requests -->
            <section
                v-else
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-surface-800 dark:bg-surface-900"
            >
                <DataTable :value="joinRequests" dataKey="id" paginator :rows="10">
                    <template #empty>
                        <div class="py-12 text-center text-slate-500 dark:text-surface-400">
                            <i class="pi pi-inbox mb-2 text-3xl"></i>
                            <p>{{ t('team_control.empty_join_requests') }}</p>
                        </div>
                    </template>

                    <Column :header="t('team_control.col_applicant')">
                        <template #body="{ data }">
                            <div>
                                <div class="font-semibold text-slate-800 dark:text-surface-0">{{ data.user_name }}</div>
                                <div class="text-xs text-slate-500 dark:text-surface-400">{{ data.user_email }}</div>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('team_control.col_team')">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-700 dark:text-surface-200">{{ data.team_name }}</span>
                                <code class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-300 text-xs font-semibold">
                                    {{ data.team_code }}
                                </code>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('team_control.col_status')">
                        <template #body="{ data }">
                            <div class="space-y-1">
                                <Tag :value="data.status.toUpperCase()" :severity="joinStatusSeverity(data.status)" />
                                <p v-if="data.rejection_reason" class="text-xs text-red-500 max-w-[12rem]">
                                    {{ data.rejection_reason }}
                                </p>
                            </div>
                        </template>
                    </Column>

                    <Column :header="t('common.actions')" headerClass="!text-right" bodyClass="!text-right">
                        <template #body="{ data }">
                            <div v-if="data.status === 'pending'" class="flex justify-end gap-2">
                                <Button
                                    :label="t('team_control.approve')"
                                    icon="pi pi-check"
                                    size="small"
                                    :loading="busyKey === `approve-join-${data.id}`"
                                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                                    @click="approveJoinRequest(data)"
                                />
                                <Button
                                    :label="t('team_control.reject')"
                                    icon="pi pi-times"
                                    severity="danger"
                                    variant="outlined"
                                    size="small"
                                    :disabled="busyKey === `approve-join-${data.id}`"
                                    @click="openRejectJoin(data)"
                                />
                            </div>
                            <span v-else class="text-xs text-slate-400 dark:text-surface-500">
                                {{ data.reviewer_name || '—' }}
                            </span>
                        </template>
                    </Column>
                </DataTable>
            </section>
        </div>

        <!-- Take Out Confirmation Dialog -->
        <Dialog
            v-model:visible="takeOutConfirmVisible"
            modal
            :draggable="false"
            :style="{ width: '28rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle text-red-500"></i>
                    <span class="text-lg font-bold">{{ t('team_control.take_out_confirm_title') }}</span>
                </div>
            </template>

            <div class="space-y-3 pt-1">
                <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                    {{ t('team_control.take_out_confirm_desc', { name: selectedMember?.name ?? '', team: selectedMember?.team_name ?? '' }) }}
                </p>

                <div
                    v-if="selectedMember?.is_team_leader"
                    class="flex items-start gap-2 rounded-xl border border-amber-200 dark:border-amber-800/70 bg-amber-50/80 dark:bg-amber-950/40 px-3 py-2.5 text-xs text-amber-800 dark:text-amber-200"
                >
                    <i class="pi pi-crown text-amber-600 dark:text-amber-400 mt-0.5 shrink-0"></i>
                    <span>{{ t('team_control.take_out_leader_warning') }}</span>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button
                        :label="t('common.cancel')"
                        severity="secondary"
                        variant="outlined"
                        :disabled="takingOut"
                        @click="takeOutConfirmVisible = false"
                    />
                    <Button
                        :label="t('team_control.take_out_confirm_submit')"
                        icon="pi pi-user-minus"
                        severity="danger"
                        :loading="takingOut"
                        @click="confirmTakeOut"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Reject Join Request Dialog -->
        <Dialog
            v-model:visible="rejectDialogVisible"
            modal
            :draggable="false"
            :style="{ width: '28rem' }"
            :breakpoints="{ '640px': '92vw' }"
        >
            <template #header>
                <div class="flex items-center gap-2">
                    <i class="pi pi-times-circle text-red-500"></i>
                    <span class="text-lg font-bold">{{ t('team_control.reject_join_title') }}</span>
                </div>
            </template>

            <div class="space-y-3 pt-1">
                <p class="text-sm text-slate-600 dark:text-surface-300">
                    {{ t('team_control.reject_join_desc', { name: rejectingJoin?.user_name ?? '', team: rejectingJoin?.team_name ?? '' }) }}
                </p>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold">{{ t('team_control.reject_reason_optional') }}</label>
                    <Textarea v-model="rejectForm.rejection_reason" rows="2" fluid />
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button
                        :label="t('common.cancel')"
                        severity="secondary"
                        variant="outlined"
                        :disabled="rejectForm.processing"
                        @click="rejectDialogVisible = false"
                    />
                    <Button
                        :label="t('team_control.reject')"
                        icon="pi pi-times"
                        severity="danger"
                        :loading="rejectForm.processing"
                        @click="submitRejectJoin"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
