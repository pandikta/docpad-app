<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/utils/formatters';

const props = defineProps({
    teamName: { type: String, default: 'My Team' },
    shareLinks: { type: Array, default: () => [] },
    members: { type: Array, default: () => [] },
    functions: { type: Array, default: () => [] },
    teamRules: { type: Array, default: () => [] },
    userRules: { type: Array, default: () => [] },
});

const activeTab = ref('links');
const busyKey = ref(null);
const copiedId = ref(null);

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
    <Head title="Team Control" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <i class="pi pi-shield text-2xl text-[#1f6f46] dark:text-emerald-400"></i>
                        <h1 class="text-2xl font-bold text-slate-800 dark:text-surface-0">Team Control</h1>
                        <Tag :value="teamName" severity="success" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-surface-400">
                        Manage active public share links and member document permissions for your team.
                    </p>
                </div>

                <Link :href="route('dashboard')">
                    <Button label="Back to Documents" icon="pi pi-arrow-left" severity="secondary" variant="outlined" />
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
                    <span>Active Share Links ({{ shareLinks.length }})</span>
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
                    <span>Member Permissions ({{ members.length }})</span>
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
                            <p>No active public share links in this team.</p>
                        </div>
                    </template>

                    <Column field="document_name" header="Document">
                        <template #body="{ data }">
                            <div class="flex flex-col">
                                <span class="font-semibold text-slate-800 dark:text-surface-0">{{ data.document_name }}</span>
                                <span class="truncate max-w-xs font-mono text-xs text-slate-400">{{ data.url }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column field="creator_name" header="Shared By" />

                    <Column field="expires_at" header="Expires">
                        <template #body="{ data }">
                            <span v-if="data.expires_at" class="text-sm">{{ formatDateTime(data.expires_at) }}</span>
                            <Tag v-else value="Never Expires" severity="secondary" />
                        </template>
                    </Column>

                    <Column header="Actions" headerClass="!text-right" bodyClass="!text-right">
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
                                    label="Revoke"
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
            <section v-else class="space-y-4">
                <div
                    v-if="!members.length"
                    class="rounded-2xl border border-slate-200 bg-white py-12 text-center text-slate-500 shadow-sm dark:border-surface-800 dark:bg-surface-900 dark:text-surface-400"
                >
                    <i class="pi pi-users mb-2 text-3xl"></i>
                    <p>No team members found to manage.</p>
                </div>

                <div
                    v-for="member in members"
                    :key="member.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-surface-800 dark:bg-surface-900"
                >
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 dark:border-surface-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold text-slate-800 dark:text-surface-0">{{ member.name }}</span>
                                <Tag v-if="member.is_team_leader" value="PM / Team Lead" severity="success" />
                                <Tag v-if="member.team_name" :value="member.team_name" severity="secondary" />
                            </div>
                            <p class="text-xs text-slate-500 dark:text-surface-400">{{ member.email }}</p>
                        </div>
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
                                        v-if="hasUserOverride(member, fn)"
                                        class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                                    >
                                        Custom
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-[11px] text-slate-400">{{ fn.code }}</span>
                                    <button
                                        v-if="hasUserOverride(member, fn)"
                                        type="button"
                                        class="text-[11px] font-semibold text-[#1f6f46] hover:underline dark:text-emerald-400 cursor-pointer"
                                        :disabled="busyKey === `reset-${member.id}-${fn.id}`"
                                        @click="resetPermission(member, fn)"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <ToggleSwitch
                                :modelValue="isEffectiveAllowed(member, fn)"
                                :disabled="busyKey === `perm-${member.id}-${fn.id}`"
                                @update:modelValue="(val) => togglePermission(member, fn, val)"
                            />
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
