<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Password from 'primevue/password';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const hasConfirmation = computed(() => form.password_confirmation.length > 0);
const passwordsMatch = computed(() => hasConfirmation.value && form.password === form.password_confirmation);
const passwordMismatch = computed(() => hasConfirmation.value && form.password !== form.password_confirmation);

const updatePassword = () => {
    if (passwordMismatch.value) return;

    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm p-6 sm:p-8">
        <header class="flex items-start gap-3.5 pb-5 border-b border-slate-100 dark:border-surface-800">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 shrink-0">
                <i class="pi pi-lock text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('profile.password_title') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-surface-400">
                    {{ t('profile.password_desc') }}
                </p>
            </div>
        </header>

        <form class="mt-6 space-y-5 max-w-xl" @submit.prevent="updatePassword">
            <!-- Current Password -->
            <div class="flex flex-col gap-1.5">
                <label for="current_password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    {{ t('profile.current_password') }}
                </label>
                <Password
                    input-id="current_password"
                    v-model="form.current_password"
                    :feedback="false"
                    toggle-mask
                    fluid
                    autocomplete="current-password"
                    placeholder="Enter current password"
                    :invalid="Boolean(form.errors.current_password)"
                />
                <small v-if="form.errors.current_password" class="text-red-500 text-xs">
                    {{ form.errors.current_password }}
                </small>
            </div>

            <!-- New Password -->
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    {{ t('profile.new_password') }}
                </label>
                <Password
                    input-id="password"
                    v-model="form.password"
                    toggle-mask
                    fluid
                    autocomplete="new-password"
                    placeholder="Enter new password"
                    :invalid="Boolean(form.errors.password)"
                />
                <small v-if="form.errors.password" class="text-red-500 text-xs">
                    {{ form.errors.password }}
                </small>
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    {{ t('profile.confirm_password') }}
                </label>
                <Password
                    input-id="password_confirmation"
                    v-model="form.password_confirmation"
                    :feedback="false"
                    toggle-mask
                    fluid
                    autocomplete="new-password"
                    placeholder="Re-enter new password"
                    :invalid="passwordMismatch || Boolean(form.errors.password_confirmation)"
                />
                <small v-if="passwordMismatch" class="flex items-center gap-1 text-red-500 text-xs">
                    <i class="pi pi-times-circle text-xs"></i>
                    <span>Passwords do not match.</span>
                </small>
                <small v-else-if="passwordsMatch" class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs">
                    <i class="pi pi-check-circle text-xs"></i>
                    <span>Passwords match.</span>
                </small>
                <small v-if="form.errors.password_confirmation" class="text-red-500 text-xs">
                    {{ form.errors.password_confirmation }}
                </small>
            </div>

            <!-- Actions -->
            <div class="pt-2 flex items-center gap-3">
                <Button
                    type="submit"
                    :label="t('profile.password_title')"
                    icon="pi pi-key"
                    :loading="form.processing"
                    :disabled="passwordMismatch"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                />
            </div>
        </form>
    </section>
</template>
