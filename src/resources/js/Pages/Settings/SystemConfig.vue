<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const props = defineProps({
    configs: {
        type: Object,
        default: () => ({ password_history_limit: 2 }),
    },
});

const form = useForm({
    password_history_limit: Number(props.configs?.password_history_limit ?? 2),
});

const submit = () => {
    form.put(route('settings.system-config.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="System Config" />

    <AppLayout>
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header Banner -->
            <section
                class="rounded-2xl bg-gradient-to-r from-[#dff2e7] to-[#e9f6ee] dark:from-emerald-950/60 dark:to-surface-900 border border-emerald-200/70 dark:border-emerald-900/50 px-6 sm:px-8 py-6"
            >
                <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-[#1f6f46] text-white shadow-sm shrink-0">
                        <i class="pi pi-sliders-h text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0">
                            System Config
                        </h1>
                        <p class="mt-0.5 text-sm text-slate-600 dark:text-surface-300">
                            Configure application security and password policies.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Security & Password Policy -->
            <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm p-6 sm:p-8">
                <header class="flex items-start gap-3.5 pb-5 border-b border-slate-100 dark:border-surface-800">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 shrink-0">
                        <i class="pi pi-shield text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">Password Policy</h2>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-surface-400">
                            Control password reuse rules across user accounts.
                        </p>
                    </div>
                </header>

                <form class="mt-6 space-y-6" @submit.prevent="submit">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <label for="password-history-limit" class="text-sm font-semibold text-slate-800 dark:text-surface-100">
                                Restrict Last
                            </label>
                            <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                                Prevent users from reusing their recent passwords. Set to <span class="font-semibold">0</span> for no restriction.
                            </p>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <InputNumber
                                inputId="password-history-limit"
                                v-model="form.password_history_limit"
                                :min="0"
                                :max="20"
                                :useGrouping="false"
                                inputClass="!w-20 !text-center !rounded-xl"
                            />
                            <span class="text-sm font-medium text-slate-700 dark:text-surface-200">
                                Password History
                            </span>
                        </div>
                    </div>

                    <small v-if="form.errors.password_history_limit" class="text-red-500 text-xs block">
                        {{ form.errors.password_history_limit }}
                    </small>

                    <div class="pt-2 border-t border-slate-100 dark:border-surface-800 flex justify-end">
                        <Button
                            type="submit"
                            :label="t('common.save_changes')"
                            icon="pi pi-check"
                            :loading="form.processing"
                            class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                        />
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
