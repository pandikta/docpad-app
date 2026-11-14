<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { getInitials } from '@/utils/formatters';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean, default: false },
    status: { type: String, default: '' },
    teamName: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth.user ?? {});
const initials = computed(() => getInitials(user.value.name));
</script>

<template>
    <Head title="Profile" />

    <AppLayout>
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Profile Summary Banner -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#dff2e7] to-[#e9f6ee] dark:from-emerald-950/60 dark:to-surface-900 border border-emerald-200/70 dark:border-emerald-900/50 px-6 sm:px-8 py-6"
            >
                <div class="relative z-10 flex flex-wrap items-center gap-5">
                    <div
                        class="flex items-center justify-center w-16 h-16 rounded-2xl bg-[#1f6f46] text-white text-xl font-bold shadow-sm shrink-0"
                    >
                        {{ initials }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="text-xl sm:text-2xl font-bold text-slate-800 dark:text-surface-0 truncate">
                                {{ user.name }}
                            </h1>
                            <span
                                v-if="teamName"
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/80 dark:bg-emerald-900/60 text-[#1f6f46] dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                            >
                                <i class="pi pi-users text-xs"></i>
                                <span>{{ teamName }}</span>
                            </span>
                        </div>
                        <p class="mt-1 flex items-center gap-2 text-sm text-slate-600 dark:text-surface-300">
                            <i class="pi pi-envelope text-xs text-[#1f6f46] dark:text-emerald-400"></i>
                            <span>{{ user.email }}</span>
                        </p>
                    </div>
                </div>
            </section>

            <!-- Forms -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />

                <UpdatePasswordForm />
            </div>

            <DeleteUserForm />
        </div>
    </AppLayout>
</template>
