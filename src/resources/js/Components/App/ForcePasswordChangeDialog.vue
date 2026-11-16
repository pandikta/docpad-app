<script setup>
import { computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Button from 'primevue/button';

defineProps({
    visible: { type: Boolean, default: false },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const passwordRules = computed(() => {
    const val = form.password ?? '';
    return {
        upper: /[A-Z]/.test(val),
        lower: /[a-z]/.test(val),
        number: /[0-9]/.test(val),
        special: /[^A-Za-z0-9]/.test(val),
        length: val.length >= 8,
    };
});

const passedRuleCount = computed(() => Object.values(passwordRules.value).filter(Boolean).length);
const allPasswordRulesMet = computed(() => passedRuleCount.value === 5);
const passwordMismatch = computed(
    () => form.password_confirmation.length > 0 && form.password !== form.password_confirmation,
);
const canSubmit = computed(
    () => allPasswordRulesMet.value && form.password_confirmation.length > 0 && !passwordMismatch.value,
);

const submit = () => {
    if (!canSubmit.value || form.processing) return;
    form.post(route('password.force-update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Dialog
        :visible="visible"
        modal
        :closable="false"
        :closeOnEscape="false"
        :dismissableMask="false"
        :draggable="false"
        :style="{ width: '30rem' }"
        :breakpoints="{ '640px': '94vw' }"
        :pt="{
            mask: { class: '!backdrop-blur-sm !bg-slate-900/50' },
        }"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200/70 dark:border-amber-800/60 flex items-center justify-center shrink-0">
                    <i class="pi pi-key text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-surface-0 leading-tight">
                        Please Change Your Password Now
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                        Your password was reset by an administrator. Set a new personal password to continue.
                    </p>
                </div>
            </div>
        </template>

        <form id="force-password-form" class="space-y-4 pt-1" autocomplete="off" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    <span class="text-red-500">*</span> New Password
                </label>
                <Password
                    v-model="form.password"
                    :feedback="false"
                    toggle-mask
                    fluid
                    placeholder="Enter new password"
                    inputClass="!rounded-xl"
                    :inputProps="{ autocomplete: 'new-password' }"
                    :invalid="Boolean(form.errors.password)"
                />

                <div class="grid grid-cols-3 gap-1.5 pt-1">
                    <div
                        class="h-1.5 rounded-full transition-colors"
                        :class="
                            passedRuleCount >= 1
                                ? passedRuleCount <= 2
                                    ? 'bg-red-500'
                                    : passedRuleCount <= 4
                                      ? 'bg-amber-500'
                                      : 'bg-[#1f6f46]'
                                : 'bg-slate-200 dark:bg-surface-700'
                        "
                    ></div>
                    <div
                        class="h-1.5 rounded-full transition-colors"
                        :class="
                            passedRuleCount >= 3
                                ? passedRuleCount <= 4
                                    ? 'bg-amber-500'
                                    : 'bg-[#1f6f46]'
                                : 'bg-slate-200 dark:bg-surface-700'
                        "
                    ></div>
                    <div
                        class="h-1.5 rounded-full transition-colors"
                        :class="passedRuleCount === 5 ? 'bg-[#1f6f46]' : 'bg-slate-200 dark:bg-surface-700'"
                    ></div>
                </div>

                <div class="flex flex-wrap gap-x-4 gap-y-1.5 pt-1 text-xs">
                    <span class="inline-flex items-center gap-1" :class="passwordRules.upper ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                        <i :class="passwordRules.upper ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                        Should contain uppercase
                    </span>
                    <span class="inline-flex items-center gap-1" :class="passwordRules.lower ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                        <i :class="passwordRules.lower ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                        Should contain lowercase
                    </span>
                    <span class="inline-flex items-center gap-1" :class="passwordRules.number ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                        <i :class="passwordRules.number ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                        Should contain number
                    </span>
                    <span class="inline-flex items-center gap-1" :class="passwordRules.special ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                        <i :class="passwordRules.special ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                        Should contain special character
                    </span>
                    <span class="inline-flex items-center gap-1" :class="passwordRules.length ? 'text-[#1f6f46] dark:text-emerald-400 font-medium' : 'text-slate-400'">
                        <i :class="passwordRules.length ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                        Password must be at least 8 characters
                    </span>
                </div>

                <small v-if="form.errors.password" class="text-red-500 text-xs">
                    {{ form.errors.password }}
                </small>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    <span class="text-red-500">*</span> Confirm Password
                </label>
                <Password
                    v-model="form.password_confirmation"
                    :feedback="false"
                    toggle-mask
                    fluid
                    placeholder="Re-enter new password"
                    inputClass="!rounded-xl"
                    :inputProps="{ autocomplete: 'new-password' }"
                    :invalid="passwordMismatch || Boolean(form.errors.password_confirmation)"
                />
                <small v-if="passwordMismatch" class="flex items-center gap-1 text-red-500 text-xs">
                    <i class="pi pi-times-circle text-xs"></i>
                    <span>Passwords do not match.</span>
                </small>
                <small v-else-if="form.password_confirmation && !passwordMismatch" class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 text-xs">
                    <i class="pi pi-check-circle text-xs"></i>
                    <span>Passwords match.</span>
                </small>
            </div>
        </form>

        <template #footer>
            <Button
                label="Logout"
                icon="pi pi-sign-out"
                severity="secondary"
                variant="outlined"
                :disabled="form.processing"
                class="!rounded-xl"
                @click="logout"
            />
            <Button
                type="submit"
                form="force-password-form"
                label="Save New Password"
                icon="pi pi-check"
                :loading="form.processing"
                :disabled="!canSubmit"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl"
            />
        </template>
    </Dialog>
</template>
