<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Password from 'primevue/password';
import { renderAsync } from 'docx-preview';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();
const appName = import.meta.env.VITE_APP_NAME || 'DocPad';

const props = defineProps({
    expired: { type: Boolean, default: false },
    requiresPassword: { type: Boolean, default: false },
    token: { type: String, default: '' },
    expiresAt: { type: String, default: null },
    document: { type: Object, default: null },
});

const unlockForm = useForm({
    password: '',
});

const submitUnlock = () => {
    if (!props.token || unlockForm.processing) return;
    unlockForm.post(route('share.verify', props.token), {
        onFinish: () => unlockForm.reset('password'),
    });
};

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
const isImage = computed(
    () =>
        Boolean(props.document?.is_stored_file) &&
        (Boolean(props.document?.mime_type?.startsWith('image/')) ||
            ['.png', '.jpg', '.jpeg', '.webp', '.gif'].some((ext) => fileNameLower.value.endsWith(ext))),
);

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

const loadDocx = async () => {
    if (props.expired || props.requiresPassword || !isDocx.value || !props.document?.preview_url) return;

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
};

onMounted(loadDocx);
watch(() => [props.requiresPassword, props.document?.preview_url], loadDocx);
</script>

<template>
    <Head :title="expired ? 'Link Expired or Revoked' : `${document?.name ?? 'Shared Document'} — DocPad`" />

    <div class="min-h-screen flex flex-col bg-slate-50 text-slate-800 dark:bg-surface-950 dark:text-surface-100">
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
                                {{ t('share.read_only') }}
                            </span>
                        </div>
                        <p v-if="!expired && document" class="truncate text-xs text-slate-500 dark:text-surface-400">
                            {{ document.name }}
                        </p>
                    </div>
                </div>

                <div v-if="!expired && !requiresPassword && document" class="flex items-center gap-2">
                    <Button
                        v-if="isPdf && document.preview_url"
                        :label="t('share.open_new_tab')"
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
                        :label="t('share.download')"
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
                {{ t('share.expired_title') }}
            </h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-surface-400">
                {{ t('share.expired_desc') }}
            </p>
            <Link href="/login" class="mt-6">
                <Button :label="t('share.go_login')" icon="pi pi-sign-in" severity="secondary" variant="outlined" />
            </Link>
        </main>

        <!-- Password / PIN Protected State -->
        <main v-else-if="requiresPassword" class="mx-auto flex max-w-md flex-col items-center justify-center px-4 py-20">
            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm dark:border-surface-800 dark:bg-surface-900">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-[#1f6f46] dark:bg-emerald-950/50 dark:text-emerald-400">
                    <i class="pi pi-lock text-2xl"></i>
                </div>
                <h1 class="mt-4 text-center text-xl font-bold text-slate-900 dark:text-surface-0">
                    {{ t('share.locked_title') }}
                </h1>
                <p class="mt-1 text-center text-sm text-slate-500 dark:text-surface-400">
                    {{ t('share.locked_desc') }}
                </p>

                <form class="mt-6 space-y-4" @submit.prevent="submitUnlock">
                    <div class="flex flex-col gap-1.5">
                        <label for="share-unlock-password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            {{ t('share.password_label') }}
                        </label>
                        <Password
                            inputId="share-unlock-password"
                            v-model="unlockForm.password"
                            :feedback="false"
                            toggleMask
                            autofocus
                            :placeholder="t('share.password_placeholder')"
                            fluid
                            :invalid="Boolean(unlockForm.errors.password)"
                            inputClass="!rounded-xl"
                        />
                        <small v-if="unlockForm.errors.password" class="text-xs text-red-500">
                            {{ unlockForm.errors.password }}
                        </small>
                    </div>

                    <Button
                        type="submit"
                        :label="t('share.unlock_button')"
                        icon="pi pi-unlock"
                        fluid
                        :loading="unlockForm.processing"
                        :disabled="!unlockForm.password"
                        class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl"
                    />
                </form>
            </div>
        </main>

        <!-- Document Content -->
        <main v-else class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
            <!-- Metadata Banner -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-500 shadow-xs dark:border-surface-800 dark:bg-surface-900 dark:text-surface-400">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="font-semibold text-slate-800 dark:text-surface-100">{{ document.name }}</span>
                    <span>{{ t('share.size') }}: {{ formattedSize }}</span>
                    <span v-if="formattedUpdatedAt">{{ t('share.updated') }}: {{ formattedUpdatedAt }}</span>
                </div>
                <span class="flex items-center gap-1.5">
                    <i class="pi pi-clock text-[11px]"></i>
                    {{ t('share.expires') }}: {{ formattedExpiresAt }}
                </span>
            </div>

            <!-- PDF Viewer -->
            <div
                v-if="isPdf"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs dark:border-surface-800 dark:bg-surface-900"
            >
                <iframe :src="document.preview_url" class="h-[78vh] w-full border-0" title="PDF Preview" />
            </div>

            <!-- Image Viewer -->
            <div
                v-else-if="isImage && document.preview_url"
                class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-4 shadow-xs sm:p-6 dark:border-surface-800 dark:bg-surface-900"
            >
                <img
                    :src="document.preview_url"
                    :alt="document.name"
                    class="max-h-[78vh] max-w-full rounded-xl object-contain"
                />
            </div>

            <!-- DOCX Viewer -->
            <div
                v-else-if="isDocx"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xs dark:border-surface-800 dark:bg-surface-900"
            >
                <div v-if="docxLoading" class="flex items-center justify-center gap-2 py-16 text-sm text-slate-500">
                    <i class="pi pi-spin pi-spinner"></i>
                    <span>{{ t('share.loading_preview') }}</span>
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
                    {{ t('share.preview_unavailable') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-surface-400">
                    {{ t('share.preview_unavailable_desc') }}
                </p>
                <Button
                    :label="t('share.download_file')"
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

        <footer class="mt-auto border-t border-slate-200/80 bg-white/80 py-4 text-center text-xs text-slate-500 dark:border-surface-800 dark:bg-surface-900/80 dark:text-surface-400">
            &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
        </footer>
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
