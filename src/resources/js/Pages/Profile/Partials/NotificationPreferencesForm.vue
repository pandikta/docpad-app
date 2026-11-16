<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ToggleSwitch from 'primevue/toggleswitch';
import { useToast } from 'primevue/usetoast';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();
const toast = useToast();
const page = usePage();
const user = computed(() => page.props.auth.user ?? {});

const form = useForm({
    email_notifications_enabled: Boolean(user.value.email_notifications_enabled ?? true),
});

const submit = () => {
    form.patch(route('profile.notifications.update'), {
        preserveScroll: true,
    });
};

const notifyTelegramSoon = () => {
    toast.add({
        severity: 'info',
        summary: t('profile.coming_soon_badge'),
        detail: t('profile.telegram_soon_toast'),
        life: 3000,
    });
};
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm p-6 sm:p-8">
        <header class="flex items-start gap-3.5 pb-5 border-b border-slate-100 dark:border-surface-800">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 shrink-0">
                <i class="pi pi-bell text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('profile.notif_title') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-surface-400">
                    {{ t('profile.notif_desc') }}
                </p>
            </div>
        </header>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <label for="email-notifications-enabled" class="text-sm font-semibold text-slate-800 dark:text-surface-100">
                        {{ t('profile.receive_email_notif') }}
                    </label>
                    <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                        {{ t('profile.receive_email_notif_hint') }}
                    </p>
                </div>

                <ToggleSwitch
                    inputId="email-notifications-enabled"
                    v-model="form.email_notifications_enabled"
                />
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-surface-800 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-800 dark:text-surface-100">
                            {{ t('profile.receive_telegram_notif') }}
                        </span>
                        <Tag :value="t('profile.coming_soon_badge')" severity="warn" />
                    </div>
                    <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                        {{ t('profile.receive_telegram_notif_hint') }}
                    </p>
                </div>

                <button
                    type="button"
                    class="cursor-pointer opacity-75 hover:opacity-100 transition-opacity"
                    @click="notifyTelegramSoon"
                >
                    <ToggleSwitch :model-value="false" readonly class="pointer-events-none" />
                </button>
            </div>

            <div class="pt-2 border-t border-slate-100 dark:border-surface-800 flex items-center justify-between gap-3">
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <span v-if="form.recentlySuccessful" class="text-xs font-medium text-[#1f6f46] dark:text-emerald-400">
                        {{ t('profile.saved') }}
                    </span>
                </Transition>

                <Button
                    type="submit"
                    :label="t('common.save_changes')"
                    icon="pi pi-check"
                    :loading="form.processing"
                    class="ml-auto !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                />
            </div>
        </form>
    </section>
</template>
