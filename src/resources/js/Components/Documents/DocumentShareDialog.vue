<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const props = defineProps({
    visible: { type: Boolean, default: false },
    document: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'generate', 'revoke']);

const expiryOptions = [
    { label: '1 Hour', value: '1h' },
    { label: '24 Hours', value: '24h' },
    { label: '7 Days', value: '7d' },
    { label: '30 Days', value: '30d' },
    { label: 'Never Expires', value: 'never' },
];

const selectedExpiry = ref('7d');
const copied = ref(false);
let copiedTimer = null;

watch(
    () => props.visible,
    (isOpen) => {
        if (isOpen) {
            selectedExpiry.value = '7d';
            copied.value = false;
        }
    },
);

const shareLink = computed(() => props.document?.share_link ?? null);

const formattedExpiry = computed(() => {
    const expiresAt = shareLink.value?.expires_at;
    if (!expiresAt) return 'Never expires';

    return new Date(expiresAt).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
});

const copyUrl = async () => {
    if (!shareLink.value?.url) return;

    try {
        await navigator.clipboard.writeText(shareLink.value.url);
        copied.value = true;
        if (copiedTimer) clearTimeout(copiedTimer);
        copiedTimer = setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        copied.value = false;
    }
};

const close = () => emit('update:visible', false);
const generate = () => emit('generate', selectedExpiry.value);
const revoke = () => emit('revoke');
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :draggable="false"
        :style="{ width: '32rem' }"
        :breakpoints="{ '640px': '94vw' }"
        @update:visible="(value) => emit('update:visible', value)"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <i class="pi pi-share-alt text-[#1f6f46] dark:text-emerald-400"></i>
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">Share Read-Only Link</span>
            </div>
        </template>

        <div class="space-y-4 pt-1">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 dark:border-surface-700 dark:bg-surface-900">
                <p class="truncate text-sm font-semibold text-slate-800 dark:text-surface-100">
                    {{ document?.name }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-surface-400">
                    Anyone with this link can view this document in read-only mode without signing in.
                </p>
            </div>

            <div v-if="shareLink" class="space-y-3">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">Public Link</label>
                    <div class="flex items-center gap-2">
                        <InputText
                            :modelValue="shareLink.url"
                            readonly
                            fluid
                            class="!rounded-xl !text-xs font-mono"
                            @focus="$event.target.select()"
                        />
                        <Button
                            :icon="copied ? 'pi pi-check' : 'pi pi-copy'"
                            :label="copied ? 'Copied' : 'Copy'"
                            :severity="copied ? 'success' : 'secondary'"
                            variant="outlined"
                            class="shrink-0"
                            @click="copyUrl"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <span class="flex items-center gap-2 font-medium">
                        <i class="pi pi-clock"></i>
                        Expires: {{ formattedExpiry }}
                    </span>
                    <Button
                        label="Open"
                        icon="pi pi-external-link"
                        text
                        size="small"
                        as="a"
                        :href="shareLink.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="!py-1 !text-xs"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    {{ shareLink ? 'Generate New Link (replaces current link)' : 'Link Expiration' }}
                </label>
                <div class="flex items-center gap-2">
                    <Select
                        v-model="selectedExpiry"
                        :options="expiryOptions"
                        optionLabel="label"
                        optionValue="value"
                        fluid
                        class="!rounded-xl"
                    />
                    <Button
                        :label="shareLink ? 'Regenerate' : 'Generate Link'"
                        icon="pi pi-link"
                        :loading="loading"
                        class="shrink-0 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                        @click="generate"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <Button
                v-if="shareLink"
                label="Revoke Link"
                icon="pi pi-ban"
                severity="danger"
                variant="outlined"
                :loading="loading"
                @click="revoke"
            />
            <Button label="Close" severity="secondary" variant="outlined" :disabled="loading" @click="close" />
        </template>
    </Dialog>
</template>
