<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import { formatDateTime } from '@/utils/formatters';
import { useLocale } from '@/composables/useLocale';

const props = defineProps({
    sessions: { type: Array, default: () => [] },
});

const { t } = useLocale();
const dialogVisible = ref(false);
const confirmVisible = ref(false);
const targetSession = ref(null);
const loggingOut = ref(false);

const currentSession = computed(
    () => props.sessions.find((s) => s.is_current) ?? props.sessions[0] ?? null,
);

const otherSessionsCount = computed(
    () => props.sessions.filter((s) => !s.is_current).length,
);

const openLogoutConfirm = (session = null) => {
    targetSession.value = session;
    confirmVisible.value = true;
};

const goToChangePassword = () => {
    dialogVisible.value = false;
    router.visit(`${route('profile.edit')}#update-password`);
};

const submitLogout = () => {
    loggingOut.value = true;
    const url = targetSession.value
        ? route('sessions.logout-one', targetSession.value.session_key)
        : route('sessions.logout-others');

    router.delete(url, {
        preserveScroll: true,
        onSuccess: () => {
            confirmVisible.value = false;
            targetSession.value = null;
        },
        onFinish: () => {
            loggingOut.value = false;
        },
    });
};
</script>

<template>
    <button
        v-if="sessions.length"
        type="button"
        class="flex items-center gap-4 px-5 py-4 rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm min-w-[14rem] text-left transition-colors hover:border-[#1f6f46] dark:hover:border-emerald-500/70 cursor-pointer"
        @click="dialogVisible = true"
    >
        <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 shrink-0">
            <i class="pi pi-shield text-lg"></i>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <p class="text-xs font-medium text-slate-500 dark:text-surface-400">
                    {{ t('dashboard.login_activity') }}
                </p>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-[#1f6f46] dark:bg-emerald-950/60 dark:text-emerald-300">
                    {{ t('dashboard.active_sessions', { count: sessions.length }) }}
                </span>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-surface-0 truncate mt-0.5">
                {{ currentSession?.device_label || 'Browser' }}
            </p>
            <p class="text-[11px] font-medium text-[#1f6f46] dark:text-emerald-400 truncate">
                {{ currentSession?.ip_address }}
            </p>
        </div>
        <i class="pi pi-chevron-right text-xs text-slate-400"></i>
    </button>

    <!-- Login Activity Dialog -->
    <Dialog
        v-model:visible="dialogVisible"
        modal
        :draggable="false"
        :style="{ width: '34rem' }"
        :breakpoints="{ '640px': '94vw' }"
    >
        <template #header>
            <div class="flex items-center justify-between gap-3 w-full pr-2">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eaf7f0] dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400">
                        <i class="pi pi-shield text-sm"></i>
                    </span>
                    <span class="text-lg font-bold text-slate-800 dark:text-surface-0">
                        {{ t('dashboard.login_activity') }}
                    </span>
                </div>
            </div>
        </template>

        <div class="space-y-4 pt-1">
            <div class="flex flex-wrap items-center justify-between gap-2.5 rounded-xl border border-blue-200/80 dark:border-blue-800/60 bg-blue-50/70 dark:bg-blue-950/30 px-3.5 py-2.5 text-xs text-blue-800 dark:text-blue-200">
                <div class="flex items-start gap-2.5 min-w-0 flex-1">
                    <i class="pi pi-info-circle text-blue-600 dark:text-blue-400 mt-0.5 shrink-0"></i>
                    <span>{{ t('dashboard.login_activity_notice') }}</span>
                </div>
                <Button
                    type="button"
                    :label="t('dashboard.change_password_cta')"
                    icon="pi pi-key"
                    size="small"
                    outlined
                    class="!border-blue-300 dark:!border-blue-700 !text-blue-700 dark:!text-blue-300 hover:!bg-blue-100/70 dark:hover:!bg-blue-900/40 !rounded-lg !text-xs shrink-0"
                    @click="goToChangePassword"
                />
            </div>

            <ul class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-surface-800">
                <li
                    v-for="session in sessions"
                    :key="session.session_key"
                    class="flex items-center gap-3.5 py-3.5 first:pt-1 last:pb-1"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 dark:bg-surface-800 text-slate-600 dark:text-surface-300">
                        <i :class="['pi text-base', session.device_type === 'mobile' ? 'pi-mobile' : 'pi-desktop']"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-bold text-slate-800 dark:text-surface-0">
                                {{ session.device_label }}
                            </span>
                            <span
                                v-if="session.is_current"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#1f6f46] dark:text-emerald-400"
                            >
                                <i class="pi pi-check-circle text-[11px]"></i>
                                <span>{{ t('dashboard.current_session') }}</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                            IP: {{ session.ip_address }}
                        </p>
                        <p class="text-[11px] text-slate-400 dark:text-surface-500 mt-0.5">
                            {{ formatDateTime(session.last_active_at) }}
                        </p>
                    </div>
                    <Button
                        v-if="!session.is_current"
                        type="button"
                        :label="t('dashboard.logout_session')"
                        icon="pi pi-sign-out"
                        severity="danger"
                        variant="outlined"
                        size="small"
                        :disabled="loggingOut"
                        class="shrink-0"
                        @click="openLogoutConfirm(session)"
                    />
                </li>
            </ul>
        </div>

        <template #footer>
            <div class="flex flex-wrap items-center justify-between gap-2 w-full pt-2">
                <Button
                    type="button"
                    :label="t('dashboard.logout_other_devices')"
                    icon="pi pi-sign-out"
                    severity="danger"
                    variant="outlined"
                    size="small"
                    :disabled="otherSessionsCount === 0 || loggingOut"
                    @click="openLogoutConfirm(null)"
                />
                <Button
                    type="button"
                    :label="t('common.close')"
                    severity="secondary"
                    variant="outlined"
                    @click="dialogVisible = false"
                />
            </div>
        </template>
    </Dialog>

    <!-- Confirm Logout Other Devices -->
    <Dialog
        v-model:visible="confirmVisible"
        modal
        :draggable="false"
        :style="{ width: '26rem' }"
        :breakpoints="{ '640px': '92vw' }"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-red-500"></i>
                <span class="text-lg font-bold">
                    {{ targetSession ? t('dashboard.logout_session') : t('dashboard.logout_other_devices') }}
                </span>
            </div>
        </template>

        <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed pt-1">
            {{
                targetSession
                    ? t('dashboard.logout_session_confirm', { device: targetSession.device_label, ip: targetSession.ip_address })
                    : t('dashboard.logout_other_confirm')
            }}
        </p>

        <template #footer>
            <Button
                :label="t('common.cancel')"
                severity="secondary"
                variant="outlined"
                :disabled="loggingOut"
                @click="confirmVisible = false"
            />
            <Button
                :label="targetSession ? t('dashboard.logout_session_submit') : t('dashboard.logout_other_submit')"
                icon="pi pi-sign-out"
                severity="danger"
                :loading="loggingOut"
                @click="submitLogout"
            />
        </template>
    </Dialog>
</template>
