<script setup>
import { ref, computed, watch } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import Popover from 'primevue/popover';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { useAuth } from '@/composables/useAuth';
import { useDocumentSearch } from '@/composables/useDocumentSearch';
import { formatFileSize, formatDateTime } from '@/utils/formatters';

const props = defineProps({
    // [{ id, name, folder_id, folder_name, is_favorite, size (bytes), created_by, created_at (ISO), updated_at (ISO), content, teams, logs }]
    documents: { type: Array, default: () => [] },
    rows: { type: Number, default: 10 },
    bulkDownloading: { type: Boolean, default: false },
    bulkDeleting: { type: Boolean, default: false },
});

const emit = defineEmits([
    'open',
    'logs',
    'download',
    'rename',
    'assign-teams',
    'toggle-visibility',
    'toggle-favorite',
    'move-folder',
    'bulk-download',
    'bulk-delete',
    'bulk-move-folder',
    'bulk-assign-teams',
    'delete',
]);

const { user, isSuperAdmin, can } = useAuth();

const isOwner = (doc) => Boolean(doc && user.value?.id && Number(doc.user_id) === Number(user.value.id));

const { searchQuery, clearSearch, matchesDocument } = useDocumentSearch();

const favoritesOnly = ref(false);
const selectedDocuments = ref([]);

watch(
    () => props.documents,
    (docs) => {
        const validIds = new Set(docs.map((d) => d.id));
        selectedDocuments.value = selectedDocuments.value.filter((d) => validIds.has(d.id));
    },
);

const clearSelection = () => {
    selectedDocuments.value = [];
};

defineExpose({ clearSelection });

const columnFilters = ref({
    name: '',
    folder: '',
    visibility: null,
    size: '',
    teams: '',
    created: '',
    updated: '',
});

const visibilityFilterOptions = [
    { label: 'Publish', value: 'publish' },
    { label: 'Private', value: 'private' },
];

const columnLabels = {
    name: 'Name',
    folder: 'Folder',
    visibility: 'Visibility',
    size: 'Size',
    teams: 'Teams',
    created: 'Created At',
    updated: 'Updated At',
};

const filterPopover = ref(null);
const activeFilterColumn = ref(null);
const draftFilterValue = ref('');

const activeColumnLabel = computed(() => columnLabels[activeFilterColumn.value] ?? '');

const isColumnFiltered = (key) => {
    const val = columnFilters.value[key];
    return typeof val === 'string' ? val.trim() !== '' : Boolean(val);
};

const openFilterPopover = (event, key) => {
    activeFilterColumn.value = key;
    draftFilterValue.value = columnFilters.value[key] ?? (key === 'visibility' ? null : '');
    filterPopover.value.toggle(event);
};

const applyColumnFilter = () => {
    if (!activeFilterColumn.value) return;
    columnFilters.value[activeFilterColumn.value] = draftFilterValue.value;
    filterPopover.value.hide();
};

const resetActiveColumnFilter = () => {
    if (!activeFilterColumn.value) return;
    columnFilters.value[activeFilterColumn.value] = activeFilterColumn.value === 'visibility' ? null : '';
    draftFilterValue.value = columnFilters.value[activeFilterColumn.value];
    filterPopover.value.hide();
};

const hasActiveColumnFilters = computed(() =>
    Boolean(
        favoritesOnly.value ||
            columnFilters.value.name.trim() ||
            columnFilters.value.folder.trim() ||
            columnFilters.value.visibility ||
            columnFilters.value.size.trim() ||
            columnFilters.value.teams.trim() ||
            columnFilters.value.created.trim() ||
            columnFilters.value.updated.trim(),
    ),
);

const clearColumnFilters = () => {
    favoritesOnly.value = false;
    columnFilters.value = {
        name: '',
        folder: '',
        visibility: null,
        size: '',
        teams: '',
        created: '',
        updated: '',
    };
};

const matchesColumnFilters = (doc) => {
    const f = columnFilters.value;

    if (favoritesOnly.value && !doc.is_favorite) {
        return false;
    }

    if (f.name.trim() && !String(doc.name ?? '').toLowerCase().includes(f.name.trim().toLowerCase())) {
        return false;
    }

    if (f.folder.trim()) {
        const folderText = (doc.folder_name || 'uncategorized').toLowerCase();
        if (!folderText.includes(f.folder.trim().toLowerCase())) {
            return false;
        }
    }

    if (f.visibility && (doc.visibility ?? 'publish') !== f.visibility) {
        return false;
    }

    if (f.size.trim()) {
        const sizeText = `${formatFileSize(doc.size)} ${doc.size ?? ''}`.toLowerCase();
        if (!sizeText.includes(f.size.trim().toLowerCase())) {
            return false;
        }
    }

    if (f.teams.trim()) {
        const teamText =
            doc.visibility === 'private'
                ? 'private only'
                : doc.teams?.length
                  ? doc.teams.map((t) => t.name).join(' ')
                  : 'unassigned';
        if (!teamText.toLowerCase().includes(f.teams.trim().toLowerCase())) {
            return false;
        }
    }

    if (f.created.trim()) {
        const createdText = `${doc.created_by ?? ''} ${formatDateTime(doc.created_at)}`.toLowerCase();
        if (!createdText.includes(f.created.trim().toLowerCase())) {
            return false;
        }
    }

    if (f.updated.trim()) {
        const updatedText = formatDateTime(doc.updated_at).toLowerCase();
        if (!updatedText.includes(f.updated.trim().toLowerCase())) {
            return false;
        }
    }

    return true;
};

const filteredDocuments = computed(() => {
    return props.documents.filter((doc) => matchesDocument(doc) && matchesColumnFilters(doc));
});

// Menu popup untuk tombol titik tiga (More actions)
const moreMenu = ref(null);
const activeRowDocument = ref(null);

const moreMenuItems = computed(() => {
    const items = [];

    if (isSuperAdmin.value && activeRowDocument.value?.visibility !== 'private') {
        items.push({
            label: 'Assign Teams',
            icon: 'pi pi-users text-[#1f6f46]',
            command: () => activeRowDocument.value && emit('assign-teams', activeRowDocument.value),
        });
    }

    if (isOwner(activeRowDocument.value)) {
        const isPrivate = activeRowDocument.value?.visibility === 'private';
        items.push({
            label: isPrivate ? 'Make Publish' : 'Make Private',
            icon: isPrivate ? 'pi pi-globe text-[#1f6f46]' : 'pi pi-lock text-amber-500',
            command: () =>
                activeRowDocument.value &&
                emit('toggle-visibility', {
                    document: activeRowDocument.value,
                    visibility: isPrivate ? 'publish' : 'private',
                }),
        });
    }

    if (can('documents.rename')) {
        items.push({
            label: 'Rename',
            icon: 'pi pi-pencil',
            command: () => activeRowDocument.value && emit('rename', activeRowDocument.value),
        });
    }

    items.push({
        label: 'Move to Folder',
        icon: 'pi pi-folder',
        command: () => activeRowDocument.value && emit('move-folder', activeRowDocument.value),
    });

    if (can('documents.delete')) {
        items.push({
            label: 'Delete',
            icon: 'pi pi-trash text-red-500',
            command: () => activeRowDocument.value && emit('delete', activeRowDocument.value),
        });
    }

    return items;
});

const hasMoreActions = (doc) =>
    (isSuperAdmin.value && doc?.visibility !== 'private') ||
    isOwner(doc) ||
    can('documents.rename') ||
    can('documents.delete');

const getDocumentIconClass = (doc) => {
    const name = String(doc?.name ?? '').toLowerCase();
    if (name.endsWith('.pdf')) return 'pi pi-file-pdf text-red-500';
    if (name.endsWith('.xls') || name.endsWith('.xlsx')) return 'pi pi-file-excel text-emerald-600 dark:text-emerald-400';
    if (name.endsWith('.doc') || name.endsWith('.docx')) return 'pi pi-file-word text-blue-500';
    if (name.endsWith('.ppt') || name.endsWith('.pptx')) return 'pi pi-desktop text-amber-500';
    if (doc?.is_stored_file) return 'pi pi-file text-slate-500';
    return 'pi pi-file-edit text-[#1f6f46] dark:text-emerald-400';
};

const toggleMoreMenu = (event, doc) => {
    activeRowDocument.value = doc;
    moreMenu.value.toggle(event);
};

const paginatorPt = {
    pcPaginator: { root: { class: '!justify-between !bg-transparent' } },
};

const actionBtnClass =
    'transition-all duration-150 hover:!border-[#1f6f46] hover:!bg-emerald-50 hover:!text-[#1f6f46] dark:hover:!border-emerald-500/70 dark:hover:!bg-emerald-950/60 dark:hover:!text-emerald-300 hover:-translate-y-0.5 hover:shadow-sm';
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm overflow-hidden">
        <!-- Header -->
        <header class="flex flex-wrap items-center justify-between gap-3 px-6 py-5">
            <div class="flex flex-wrap items-center gap-3">
                <i class="pi pi-file text-xl text-[#1f6f46] dark:text-emerald-400"></i>
                <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">List Documents</h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border transition-colors cursor-pointer"
                    :class="
                        favoritesOnly
                            ? 'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-700'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-amber-300 dark:bg-surface-900 dark:text-surface-300 dark:border-surface-700'
                    "
                    @click="favoritesOnly = !favoritesOnly"
                >
                    <i :class="favoritesOnly ? 'pi pi-star-fill text-amber-500 text-[11px]' : 'pi pi-star text-[11px]'"></i>
                    <span>Favorites</span>
                </button>
                <span
                    v-if="searchQuery.trim()"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300"
                >
                    <span>"{{ searchQuery.trim() }}" ({{ filteredDocuments.length }})</span>
                    <button type="button" class="hover:opacity-75 cursor-pointer" @click="clearSearch">
                        <i class="pi pi-times text-[10px]"></i>
                    </button>
                </span>
                <button
                    v-if="hasActiveColumnFilters"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-[#1f6f46] hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 cursor-pointer transition-colors"
                    @click="clearColumnFilters"
                >
                    <i class="pi pi-filter-slash text-[10px]"></i>
                    <span>Clear filters</span>
                </button>
            </div>
            <slot name="header-actions" />
        </header>

        <!-- Bulk Action Bar -->
        <div
            v-if="selectedDocuments.length"
            class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 bg-emerald-50/80 dark:bg-emerald-950/40 border-y border-emerald-200/70 dark:border-emerald-800/60"
        >
            <div class="flex items-center gap-2.5 text-sm">
                <span class="font-semibold text-[#1f6f46] dark:text-emerald-300">
                    {{ selectedDocuments.length }} selected
                </span>
                <button
                    type="button"
                    class="text-xs text-slate-500 hover:text-slate-800 dark:text-surface-400 dark:hover:text-surface-100 underline cursor-pointer"
                    @click="clearSelection"
                >
                    Clear
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-if="can('documents.download')"
                    label="Download ZIP"
                    icon="pi pi-download"
                    size="small"
                    severity="secondary"
                    variant="outlined"
                    :loading="bulkDownloading"
                    class="!rounded-lg"
                    @click="$emit('bulk-download', selectedDocuments.map((d) => d.id))"
                />
                <Button
                    label="Move Folder"
                    icon="pi pi-folder"
                    size="small"
                    severity="secondary"
                    variant="outlined"
                    class="!rounded-lg"
                    @click="$emit('bulk-move-folder', selectedDocuments.map((d) => d.id))"
                />
                <Button
                    v-if="isSuperAdmin"
                    label="Assign Teams"
                    icon="pi pi-users"
                    size="small"
                    severity="secondary"
                    variant="outlined"
                    class="!rounded-lg"
                    @click="$emit('bulk-assign-teams', selectedDocuments.map((d) => d.id))"
                />
                <Button
                    v-if="can('documents.delete')"
                    label="Delete"
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    variant="outlined"
                    :loading="bulkDeleting"
                    class="!rounded-lg"
                    @click="$emit('bulk-delete', selectedDocuments.map((d) => d.id))"
                />
            </div>
        </div>

        <DataTable
            v-model:selection="selectedDocuments"
            :value="filteredDocuments"
            dataKey="id"
            paginator
            :rows="rows"
            paginatorTemplate="CurrentPageReport PrevPageLink PageLinks NextPageLink"
            currentPageReportTemplate="Showing {first}–{last} of {totalRecords} items"
            :pt="paginatorPt"
        >
            <template #empty>
                <div class="py-10 text-center text-slate-500 dark:text-surface-400">
                    <i class="pi pi-inbox text-3xl mb-2"></i>
                    <p v-if="searchQuery.trim()">
                        No documents matching <span class="font-semibold">"{{ searchQuery.trim() }}"</span>.
                    </p>
                    <p v-else-if="hasActiveColumnFilters">
                        No documents matching your column filters.
                    </p>
                    <p v-else>No documents found.</p>
                    <div v-if="searchQuery.trim() || hasActiveColumnFilters" class="mt-2 flex items-center justify-center gap-3">
                        <button
                            v-if="searchQuery.trim()"
                            type="button"
                            class="text-xs font-semibold text-[#1f6f46] dark:text-emerald-400 hover:underline cursor-pointer"
                            @click="clearSearch"
                        >
                            Clear search
                        </button>
                        <button
                            v-if="hasActiveColumnFilters"
                            type="button"
                            class="text-xs font-semibold text-[#1f6f46] dark:text-emerald-400 hover:underline cursor-pointer"
                            @click="clearColumnFilters"
                        >
                            Clear column filters
                        </button>
                    </div>
                </div>
            </template>

            <Column selectionMode="multiple" headerStyle="width: 3rem" />

            <!-- Name -->
            <Column field="name" sortable>
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Name</span>
                        <button
                            type="button"
                            v-tooltip.top="'Search Name'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('name') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'name')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">
                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            v-tooltip.top="data.is_favorite ? 'Remove from Favorites' : 'Pin to Favorites'"
                            class="p-1 rounded hover:bg-amber-50 dark:hover:bg-amber-950/40 cursor-pointer transition-colors shrink-0"
                            @click.stop="$emit('toggle-favorite', data)"
                        >
                            <i
                                :class="
                                    data.is_favorite
                                        ? 'pi pi-star-fill text-amber-400 text-sm'
                                        : 'pi pi-star text-slate-300 hover:text-amber-400 dark:text-surface-600 text-sm'
                                "
                            ></i>
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-2.5 text-left font-medium text-slate-700 dark:text-surface-100 hover:text-[#1f6f46] dark:hover:text-emerald-400 hover:underline cursor-pointer"
                            @click="$emit('open', data)"
                        >
                            <i :class="getDocumentIconClass(data)"></i>
                            <span>{{ data.name }}</span>
                        </button>
                    </div>
                </template>
            </Column>

            <!-- Folder -->
            <Column field="folder_name" sortable>
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Folder</span>
                        <button
                            type="button"
                            v-tooltip.top="'Search Folder'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('folder') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'folder')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">
                    <span
                        v-if="data.folder_name"
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-surface-800 dark:text-surface-200 border border-slate-200 dark:border-surface-700"
                    >
                        <i class="pi pi-folder text-[10px] text-[#1f6f46] dark:text-emerald-400"></i>
                        <span>{{ data.folder_name }}</span>
                    </span>
                    <span v-else class="text-xs text-slate-400 italic">Uncategorized</span>
                </template>
            </Column>

            <!-- Visibility -->
            <Column field="visibility" sortable>
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Visibility</span>
                        <button
                            type="button"
                            v-tooltip.top="'Filter Visibility'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('visibility') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'visibility')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">
                    <span
                        v-if="data.visibility === 'private'"
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-surface-800 dark:text-surface-200 border border-slate-200 dark:border-surface-700"
                    >
                        <i class="pi pi-lock text-[10px]"></i>
                        <span>Private</span>
                    </span>
                    <span
                        v-else
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/60"
                    >
                        <i class="pi pi-globe text-[10px]"></i>
                        <span>Publish</span>
                    </span>
                </template>
            </Column>

            <!-- Size -->
            <Column field="size" sortable>
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Size</span>
                        <button
                            type="button"
                            v-tooltip.top="'Search Size'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('size') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'size')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">{{ formatFileSize(data.size) }}</template>
            </Column>

            <!-- Teams (khusus Superadmin) -->
            <Column v-if="isSuperAdmin">
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Teams</span>
                        <button
                            type="button"
                            v-tooltip.top="'Search Teams'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('teams') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'teams')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">
                    <span
                        v-if="data.visibility === 'private'"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500 dark:bg-surface-800 dark:text-surface-400"
                    >
                        <i class="pi pi-lock text-[10px]"></i>
                        <span>Private Only</span>
                    </span>
                    <div v-else class="flex flex-wrap items-center gap-1.5">
                        <template v-if="data.teams?.length">
                            <span
                                v-for="team in data.teams"
                                :key="team.id"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-800/60"
                            >
                                <i class="pi pi-users text-[10px]"></i>
                                <span>{{ team.name }}</span>
                            </span>
                        </template>
                        <button
                            v-else
                            type="button"
                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800/60 cursor-pointer transition-colors"
                            @click="$emit('assign-teams', data)"
                        >
                            <i class="pi pi-exclamation-circle text-[10px]"></i>
                            <span>Unassigned</span>
                        </button>
                    </div>
                </template>
            </Column>

            <!-- Created At (siapa yang membuat & jamnya) -->
            <Column field="created_at" sortable>
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Created At</span>
                        <button
                            type="button"
                            v-tooltip.top="'Search Created At'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('created') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'created')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">
                    <div class="flex flex-col">
                        <span class="text-sm font-medium text-slate-700 dark:text-surface-100">
                            {{ data.created_by }}
                        </span>
                        <span class="text-xs text-slate-500 dark:text-surface-400">
                            {{ formatDateTime(data.created_at) }}
                        </span>
                    </div>
                </template>
            </Column>

            <!-- Updated At -->
            <Column field="updated_at" sortable>
                <template #header>
                    <div class="flex items-center justify-between gap-2 w-full">
                        <span>Updated At</span>
                        <button
                            type="button"
                            v-tooltip.top="'Search Updated At'"
                            class="p-1 rounded-md hover:bg-slate-100 dark:hover:bg-surface-800 cursor-pointer transition-colors"
                            :class="isColumnFiltered('updated') ? 'text-[#1f6f46] dark:text-emerald-400' : 'text-slate-400 dark:text-surface-400'"
                            @click.stop="openFilterPopover($event, 'updated')"
                        >
                            <i class="pi pi-search text-xs"></i>
                        </button>
                    </div>
                </template>
                <template #body="{ data }">{{ formatDateTime(data.updated_at) }}</template>
            </Column>

            <!-- Actions -->
            <Column header="Actions" headerClass="!text-right" bodyClass="!text-right">
                <template #body="{ data }">
                    <div class="flex justify-end gap-2">
                        <Button
                            v-if="can('documents.logs')"
                            v-tooltip.top="'Document Logs'"
                            icon="pi pi-history"
                            severity="secondary"
                            variant="outlined"
                            size="small"
                            :class="actionBtnClass"
                            aria-label="Document Logs"
                            @click="$emit('logs', data)"
                        />
                        <Button
                            v-if="can('documents.download')"
                            v-tooltip.top="'Download'"
                            icon="pi pi-download"
                            severity="secondary"
                            variant="outlined"
                            size="small"
                            :class="actionBtnClass"
                            aria-label="Download"
                            @click="$emit('download', data)"
                        />
                        <Button
                            v-if="hasMoreActions(data)"
                            v-tooltip.top="'More actions'"
                            icon="pi pi-ellipsis-v"
                            severity="secondary"
                            variant="outlined"
                            size="small"
                            :class="actionBtnClass"
                            aria-label="More actions"
                            aria-haspopup="true"
                            @click="toggleMoreMenu($event, data)"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <Menu ref="moreMenu" :model="moreMenuItems" popup />

        <Popover ref="filterPopover" class="w-72">
            <div class="space-y-3 p-1">
                <Select
                    v-if="activeFilterColumn === 'visibility'"
                    v-model="draftFilterValue"
                    :options="visibilityFilterOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Visibility"
                    appendTo="self"
                    showClear
                    class="w-full"
                />
                <InputText
                    v-else
                    v-model="draftFilterValue"
                    :placeholder="`Search ${activeColumnLabel}`"
                    class="w-full"
                    autofocus
                    @keydown.enter="applyColumnFilter"
                />

                <div class="flex items-center gap-2">
                    <Button
                        label="Search"
                        size="small"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !rounded-full !px-4"
                        @click="applyColumnFilter"
                    />
                    <Button
                        v-if="activeFilterColumn && isColumnFiltered(activeFilterColumn)"
                        label="Reset"
                        severity="secondary"
                        variant="outlined"
                        size="small"
                        class="!rounded-full"
                        @click="resetActiveColumnFilter"
                    />
                </div>
            </div>
        </Popover>
    </section>
</template>
