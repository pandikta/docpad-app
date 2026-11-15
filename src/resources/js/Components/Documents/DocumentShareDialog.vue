<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    visible: { type: Boolean, default: false },
    document: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'generate', 'update-password', 'revoke']);

const expiryOptions = [
    { label: '1 Hour', value: '1h' },
    { label: '24 Hours', value: '24h' },
    { label: '7 Days', value: '7d' },
    { label: '30 Days', value: '30d' },
    { label: 'Never Expires', value: 'never' },
];

const selectedExpiry = ref('7d');
const sharePassword = ref('');
const notifyMembers = ref(false);
const copied = ref(false);
let copiedTimer = null;

watch(
    () => props.visible,
    (isOpen) => {
        if (isOpen) {
            selectedExpiry.value = '7d';
            sharePassword.value = '';
            notifyMembers.value = false;
            copied.value = false;
        }
    },
);

const shareLink = computed(() => props.document?.share_link ?? null);

const formattedExpiry = computed(() => {
    const expiresAt = shareLink.value?.expires_at;
    if (!expiresAt) return t('dialogs.share_never_expires');

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
const generate = () =>
    emit('generate', {
        expiry: selectedExpiry.value,
        password: sharePassword.value.trim() || null,
        notify_members: notifyMembers.value,
    });
const updatePassword = (remove = false) => {
    emit('update-password', {
        password: remove ? null : sharePassword.value.trim() || null,
        notify_members: notifyMembers.value,
    });
    sharePassword.value = '';
};
const revoke = () => emit('revoke', { notify_members: notifyMembers.value });
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
                <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('dialogs.share_title') }}</span>
            </div>
        </template>

        <div class="space-y-4 pt-1">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 dark:border-surface-700 dark:bg-surface-900">
                <p class="truncate text-sm font-semibold text-slate-800 dark:text-surface-100">
                    {{ document?.name }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-surface-400">
                    {{ t('dialogs.share_desc') }}
                </p>
            </div>

            <div v-if="shareLink" class="space-y-3">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">{{ t('dialogs.share_public_link') }}</label>
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
                            :label="copied ? t('dialogs.share_copied') : t('dialogs.share_copy')"
                            :severity="copied ? 'success' : 'secondary'"
                            variant="outlined"
                            class="shrink-0"
                            @click="copyUrl"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/70 px-3.5 py-2.5 text-xs text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                    <div class="flex flex-wrap items-center gap-2 font-medium">
                        <span class="flex items-center gap-1.5">
                            <i class="pi pi-clock"></i>
                            {{ t('dialogs.share_expires', { date: formattedExpiry }) }}
                        </span>
                        <span
                            v-if="shareLink.has_password"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                        >
                            <i class="pi pi-lock text-[9px]"></i>
                            {{ t('dialogs.share_password_protected') }}
                        </span>
                    </div>
                    <Button
                        :label="t('dialogs.share_open')"
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
                    {{ shareLink ? t('dialogs.share_new_label') : t('dialogs.share_expiration_label') }}
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
                        :label="shareLink ? t('dialogs.share_regenerate') : t('dialogs.share_generate')"
                        icon="pi pi-link"
                        :loading="loading"
                        class="shrink-0 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                        @click="generate"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between gap-2">
                    <label for="share-password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                        {{ t('dialogs.share_password_label') }}
                    </label>
                    <button
                        v-if="shareLink?.has_password"
                        type="button"
                        :disabled="loading"
                        class="text-xs font-semibold text-red-500 hover:underline cursor-pointer disabled:opacity-50"
                        @click="updatePassword(true)"
                    >
                        {{ t('dialogs.share_remove_password') }}
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <Password
                        inputId="share-password"
                        v-model="sharePassword"
                        :feedback="false"
                        toggleMask
                        autocomplete="new-password"
                        :placeholder="t('dialogs.share_password_placeholder')"
                        fluid
                        inputClass="!rounded-xl !text-sm"
                    />
                    <Button
                        v-if="shareLink"
                        :label="t('dialogs.share_reset_password')"
                        icon="pi pi-key"
                        severity="secondary"
                        variant="outlined"
                        :loading="loading"
                        :disabled="sharePassword.trim().length < 4"
                        class="shrink-0"
                        @click="updatePassword(false)"
                    />
                </div>
            </div>

            <!-- Notify Team Members -->
            <div class="flex items-center gap-2.5 pt-1">
                <Checkbox v-model="notifyMembers" inputId="share-notify-members" binary />
                <label for="share-notify-members" class="text-sm text-slate-700 dark:text-surface-200 cursor-pointer select-none">
                    {{ t('dialogs.notify_members_share') }}
                </label>
            </div>
        </div>

        <template #footer>
            <Button
                v-if="shareLink"
                :label="t('dialogs.share_revoke')"
                icon="pi pi-ban"
                severity="danger"
                variant="outlined"
                :loading="loading"
                @click="revoke"
            />
            <Button :label="t('common.close')" severity="secondary" variant="outlined" :disabled="loading" @click="close" />
        </template>
    </Dialog>
</template>
