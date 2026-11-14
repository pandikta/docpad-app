<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Button from 'primevue/button';
import Message from 'primevue/message';

defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: '' },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section class="rounded-2xl bg-white dark:bg-surface-900 border border-slate-200 dark:border-surface-800 shadow-sm p-6 sm:p-8">
        <header class="flex items-start gap-3.5 pb-5 border-b border-slate-100 dark:border-surface-800">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400 shrink-0">
                <i class="pi pi-id-card text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 dark:text-surface-0">Profile Information</h2>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-surface-400">
                    Update your account's profile information and email address.
                </p>
            </div>
        </header>

        <form class="mt-6 space-y-5 max-w-xl" @submit.prevent="submit">
            <!-- Name -->
            <div class="flex flex-col gap-1.5">
                <label for="name" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    Full Name
                </label>
                <IconField>
                    <InputIcon class="pi pi-user" />
                    <InputText
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autocomplete="name"
                        fluid
                        placeholder="Your full name"
                        :invalid="Boolean(form.errors.name)"
                    />
                </IconField>
                <small v-if="form.errors.name" class="text-red-500 text-xs">
                    {{ form.errors.name }}
                </small>
            </div>

            <!-- Email -->
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    Email Address
                </label>
                <IconField>
                    <InputIcon class="pi pi-envelope" />
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="username"
                        fluid
                        placeholder="you@example.com"
                        :invalid="Boolean(form.errors.email)"
                    />
                </IconField>
                <small v-if="form.errors.email" class="text-red-500 text-xs">
                    {{ form.errors.email }}
                </small>
            </div>

            <!-- Unverified email notice -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="space-y-2">
                <Message severity="warn" :closable="false">
                    <span class="text-sm">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="font-semibold underline hover:opacity-80 ml-1 cursor-pointer"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </span>
                </Message>

                <Message v-if="status === 'verification-link-sent'" severity="success" :closable="false">
                    A new verification link has been sent to your email address.
                </Message>
            </div>

            <!-- Actions -->
            <div class="pt-2 flex items-center gap-3">
                <Button
                    type="submit"
                    label="Save Changes"
                    icon="pi pi-check"
                    :loading="form.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                />
            </div>
        </form>
    </section>
</template>
