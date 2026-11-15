<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Button from 'primevue/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const confirmingUserDeletion = ref(false);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-surface-900 border border-red-200 dark:border-red-950/80 shadow-sm p-6 sm:p-8">
        <header class="flex items-start gap-3.5 pb-5 border-b border-red-100 dark:border-red-950/60">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 shrink-0">
                <i class="pi pi-exclamation-triangle text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('profile.delete_title') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-surface-400">
                    {{ t('profile.delete_desc') }}
                </p>
            </div>
        </header>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-slate-600 dark:text-surface-300 max-w-xl">
                {{ t('profile.delete_desc') }}
            </p>

            <Button
                type="button"
                :label="t('profile.delete_button')"
                icon="pi pi-trash"
                severity="danger"
                class="!rounded-xl !font-semibold !px-5"
                @click="confirmUserDeletion"
            />
        </div>

        <Dialog
            v-model:visible="confirmingUserDeletion"
            modal
            :draggable="false"
            :style="{ width: '28rem' }"
            :breakpoints="{ '640px': '92vw' }"
            @hide="closeModal"
        >
            <template #header>
                <div class="flex items-center gap-2.5">
                    <i class="pi pi-exclamation-triangle text-red-500"></i>
                    <span class="text-lg font-bold text-slate-800 dark:text-surface-0">{{ t('profile.delete_title') }}</span>
                </div>
            </template>

            <form class="space-y-4" @submit.prevent="deleteUser">
                <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                    {{ t('profile.delete_confirm_desc') }}
                </p>

                <div class="flex flex-col gap-1.5">
                    <label for="delete_password" class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-surface-400">
                        {{ t('profile.confirm_password') }}
                    </label>
                    <Password
                        input-id="delete_password"
                        v-model="form.password"
                        :feedback="false"
                        toggle-mask
                        fluid
                        placeholder="Enter your password"
                        :invalid="Boolean(form.errors.password)"
                    />
                    <small v-if="form.errors.password" class="text-red-500 text-xs">
                        {{ form.errors.password }}
                    </small>
                </div>
            </form>

            <template #footer>
                <Button :label="t('common.cancel')" severity="secondary" variant="outlined" @click="closeModal" />
                <Button
                    :label="t('profile.delete_button')"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="form.processing"
                    :disabled="!form.password"
                    @click="deleteUser"
                />
            </template>
        </Dialog>
    </section>
</template>
