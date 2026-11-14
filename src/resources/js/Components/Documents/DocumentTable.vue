<script setup>
import { ref, computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { useDocumentSearch } from '@/composables/useDocumentSearch';
import { formatFileSize, formatDateTime } from '@/utils/formatters';

const props = defineProps({
    // [{ id, name, size (bytes), created_by, created_at (ISO), updated_at (ISO), content, logs }]
    documents: { type: Array, default: () => [] },
    rows: { type: Number, default: 10 },
});

// open     : nama dokumen diklik (buka editor)
// logs     : tombol log aktivitas diklik
// download : tombol download diklik
// rename   : aksi rename dipilih dari menu titik tiga
// delete   : aksi delete dipilih dari menu titik tiga
const emit = defineEmits(['open', 'logs', 'download', 'rename', 'delete']);

const { searchQuery, clearSearch, matchesDocument } = useDocumentSearch();

const filteredDocuments = computed(() => {
    return props.documents.filter((doc) => matchesDocument(doc));
});

// Menu popup untuk tombol titik tiga (More actions)
const moreMenu = ref(null);
const activeRowDocument = ref(null);

const moreMenuItems = [
    {
        label: 'Rename',
        icon: 'pi pi-pencil',
        command: () => activeRowDocument.value && emit('rename', activeRowDocument.value),
    },
    {
        label: 'Delete',
        icon: 'pi pi-trash text-red-500',
        command: () => activeRowDocument.value && emit('delete', activeRowDocument.value),
    },
];

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
        <header class="flex items-center justify-between gap-3 px-6 py-5">
            <div class="flex items-center gap-3">
                <i class="pi pi-file text-xl text-[#1f6f46] dark:text-emerald-400"></i>
                <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">List Documents</h2>
                <span
                    v-if="searchQuery.trim()"
                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300"
                >
                    <span>"{{ searchQuery.trim() }}" ({{ filteredDocuments.length }})</span>
                    <button type="button" class="hover:opacity-75 cursor-pointer" @click="clearSearch">
                        <i class="pi pi-times text-[10px]"></i>
                    </button>
                </span>
            </div>
            <slot name="header-actions" />
        </header>

        <DataTable
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
                    <p v-else>No documents found.</p>
                    <button
                        v-if="searchQuery.trim()"
                        type="button"
                        class="mt-2 text-xs font-semibold text-[#1f6f46] dark:text-emerald-400 hover:underline cursor-pointer"
                        @click="clearSearch"
                    >
                        Clear search
                    </button>
                </div>
            </template>

            <!-- Name -->
            <Column field="name" header="Name">
                <template #body="{ data }">
                    <button
                        type="button"
                        class="flex items-center gap-3 text-left font-medium text-slate-700 dark:text-surface-100 hover:text-[#1f6f46] dark:hover:text-emerald-400 hover:underline cursor-pointer"
                        @click="$emit('open', data)"
                    >
                        <i class="pi pi-file-edit text-[#1f6f46] dark:text-emerald-400"></i>
                        <span>{{ data.name }}</span>
                    </button>
                </template>
            </Column>

            <!-- Size -->
            <Column field="size" header="Size">
                <template #body="{ data }">{{ formatFileSize(data.size) }}</template>
            </Column>

            <!-- Created At (siapa yang membuat & jamnya) -->
            <Column field="created_at" header="Created At">
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
            <Column field="updated_at" header="Updated At">
                <template #body="{ data }">{{ formatDateTime(data.updated_at) }}</template>
            </Column>

            <!-- Actions -->
            <Column header="Actions" headerClass="!text-right" bodyClass="!text-right">
                <template #body="{ data }">
                    <div class="flex justify-end gap-2">
                        <Button
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
    </section>
</template>
