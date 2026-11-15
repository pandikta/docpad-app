<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import { renderAsync } from 'docx-preview';

const props = defineProps({
    expired: { type: Boolean, default: false },
    expiresAt: { type: String, default: null },
    document: { type: Object, default: null },
});

const docxContainer = ref(null);
const docxLoading = ref(false);
const docxError = ref(null);

const fileNameLower = computed(() => String(props.document?.name ?? '').toLowerCase());
const isPdf = computed(
    () =>
        Boolean(props.document?.is_stored_file) &&
        (props.document?.mime_type === 'application/pdf' || fileNameLower.value.endsWith('.pdf')),
);
const isDocx = computed(() => Boolean(props.document?.is_stored_file) && fileNameLower.value.endsWith('.docx'));

const formattedSize = computed(() => {
    const bytes = Number(props.document?.size ?? 0);
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
});

const formattedUpdatedAt = computed(() => {
    if (!props.document?.updated_at) return null;
    return new Date(props.document.updated_at).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

const formattedExpiresAt = computed(() => {
    if (!props.expiresAt) return 'Never expires';
    return new Date(props.expiresAt).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

onMounted(async () => {
    if (props.expired || !isDocx.value || !props.document?.preview_url) return;

    docxLoading.value = true;
    docxError.value = null;

    try {
        const response = await fetch(props.document.preview_url);
        if (!response.ok) {
            throw new Error('Failed to fetch document preview.');
        }

        const buffer = await response.arrayBuffer();
        await nextTick();
        if (docxContainer.value) {
            docxContainer.value.innerHTML = '';
            await renderAsync(buffer, docxContainer.value, undefined, {
                className: 'docx-preview',
                inWrapper: true,
                ignoreWidth: false,
                ignoreHeight: false,
            });
        }
    } catch (error) {
        docxError.value = error?.message || 'Unable to render DOCX preview.';
    } finally {
        docxLoading.value = false;
    }
});
</script>

<template>
    <Head :title="expired ? 'Link Expired or Revoked' : `${document?.name ?? 'Shared Document'} — DocPad`" />

    <div class="min-h-screen bg-slate-50 text-slate-800 dark:bg-surface-950 dark:text-surface-100">
        <!-- Top Bar -->
        <header class="border-b border-slate-200 bg-white/95 backdrop-blur dark:border-surface-800 dark:bg-surface-900/95">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-6">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1f6f46] text-white font-bold">
                        D
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold tracking-tight text-slate-900 dark:text-surface-0">DocPad</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300">
                                <i class="pi pi-eye text-[10px]"></i>
                                Read-Only
                            </span>
                        </div>
                        <p v-if="!expired && document" class="truncate text-xs text-slate-500 dark:text-surface-400">
                            {{ document.name }}
                        </p>
                    </div>
                </div>

                <div v-if="!expired && document" class="flex items-center gap-2">
                    <Button
                        v-if="isPdf && document.preview_url"
                        label="Open in New Tab"
                        icon="pi pi-external-link"
                        severity="secondary"
                        variant="outlined"
                        size="small"
                        as="a"
                        :href="document.preview_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    />
                    <Button
                        label="Download"
                        icon="pi pi-download"
                        size="small"
                        as="a"
                        :href="document.download_url"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    />
                </div>
            </div>
        </header>

        <!-- Expired / Revoked State -->
        <main v-if="expired || !document" class="mx-auto flex max-w-lg flex-col items-center justify-center px-4 py-24 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500 dark:bg-red-950/40 dark:text-red-400">
                <i class="pi pi-link-slash text-2xl"></i>
            </div>
            <h1 class="mt-5 text-2xl font-bold text-slate-900 dark:text-surface-0">
                Link Expired or Revoked
            </h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-surface-400">
                This shared document link is no longer active. Please ask the document owner to generate a new share link.
            </p>
            <Link href="/login" class="mt-6">
                <Button label="Go to DocPad Login" icon="pi pi-sign-in" severity="secondary" variant="outlined" />
            </Link>
        </main>

        <!-- Document Content -->
        <main v-else class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <!-- Metadata Banner -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 shadow-xs dark:border-surface-800 dark:bg-surface-900 dark:text-surface-400">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="font-semibold text-slate-800 dark:text-surface-100">{{ document.name }}</span>
                    <span>Size: {{ formattedSize }}</span>
                    <span v-if="formattedUpdatedAt">Updated: {{ formattedUpdatedAt }}</span>
                </div>
                <span class="flex items-center gap-1.5">
                    <i class="pi pi-clock text-[11px]"></i>
                    Expires: {{ formattedExpiresAt }}
                </span>
            </div>

            <!-- PDF Viewer -->
            <div
                v-if="isPdf"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-surface-800 dark:bg-surface-900"
            >
                <iframe :src="document.preview_url" class="h-[78vh] w-full border-0" title="PDF Preview" />
            </div>

            <!-- DOCX Viewer -->
            <div
                v-else-if="isDocx"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-surface-800 dark:bg-surface-900"
            >
                <div v-if="docxLoading" class="flex items-center justify-center gap-2 py-16 text-sm text-slate-500">
                    <i class="pi pi-spin pi-spinner"></i>
                    <span>Loading document preview...</span>
                </div>
                <div v-else-if="docxError" class="py-12 text-center text-sm text-red-500">
                    {{ docxError }}
                </div>
                <div ref="docxContainer" class="overflow-x-auto"></div>
            </div>

            <!-- Other Binary Files -->
            <div
                v-else-if="document.is_stored_file"
                class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-xs dark:border-surface-800 dark:bg-surface-900"
            >
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-surface-800 dark:text-surface-300">
                    <i class="pi pi-file text-2xl"></i>
                </div>
                <h2 class="mt-4 text-base font-semibold text-slate-800 dark:text-surface-100">
                    Preview is not available for this file type
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-surface-400">
                    You can download the file to view it on your device.
                </p>
                <Button
                    label="Download File"
                    icon="pi pi-download"
                    as="a"
                    :href="document.download_url"
                    class="mt-5 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                />
            </div>

            <!-- Rich Text / Note Document -->
            <article
                v-else
                class="docpad-readonly-content min-h-[60vh] overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-xs sm:p-10 dark:border-surface-800 dark:bg-surface-900"
                v-html="document.content || '<p class=\'text-slate-400 italic\'>Empty document.</p>'"
            />
        </main>
    </div>
</template>

<style scoped>
.docpad-readonly-content {
    overflow-wrap: anywhere;
    word-break: break-word;
}
.docpad-readonly-content :deep(*) {
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.docpad-readonly-content :deep(h1) {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}
.docpad-readonly-content :deep(h2) {
    font-size: 1.35rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}
.docpad-readonly-content :deep(p) {
    margin-bottom: 0.75rem;
    line-height: 1.7;
}
.docpad-readonly-content :deep(ul),
.docpad-readonly-content :deep(ol) {
    margin-left: 1.5rem;
    margin-bottom: 0.75rem;
}
.docpad-readonly-content :deep(ul) {
    list-style-type: disc;
}
.docpad-readonly-content :deep(ol) {
    list-style-type: decimal;
}
.docpad-readonly-content :deep(pre) {
    background: rgba(15, 23, 42, 0.06);
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
    word-break: break-word;
    margin-bottom: 0.75rem;
}
</style>
