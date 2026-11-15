<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const appName = import.meta.env.VITE_APP_NAME || 'DocPad';
const isDark = ref(false);

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark-mode', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark-mode');
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-white dark:bg-surface-950">
        <!-- Left Column: Branding & Illustration Area -->
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-[#eaf7f0] dark:bg-emerald-950/40 px-14 pt-12 pb-10 border-r border-[#d8eee2] dark:border-surface-800">
            <!-- Subtle Background Wavy Shapes -->
            <svg
                class="absolute inset-x-0 bottom-0 w-full h-[55%] pointer-events-none select-none"
                viewBox="0 0 800 500"
                fill="none"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    d="M520 140C620 40 730 30 800 60V500H0V310C90 260 220 270 330 290C430 308 440 220 520 140Z"
                    fill="#dff3e8"
                    class="dark:fill-emerald-900/20"
                />
                <path
                    d="M0 350C120 300 260 320 390 365C520 410 670 340 800 270V500H0V350Z"
                    fill="#d2ede0"
                    class="dark:fill-emerald-900/30"
                />
                <path
                    d="M0 430C180 390 340 440 520 415C660 395 740 360 800 350V500H0V430Z"
                    fill="#c5e7d6"
                    class="dark:fill-emerald-900/40"
                />
            </svg>

            <!-- Top Brand Logo -->
            <div class="relative z-10 flex items-center gap-3.5">
                <svg class="w-11 h-9 shrink-0" viewBox="0 0 48 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M3 6C3 3.79086 4.79086 2 7 2H17.3431C18.404 2 19.4214 2.42143 20.1716 3.17157L23.8284 6.82843C24.5786 7.57857 25.596 8 26.6569 8H41C43.2091 8 45 9.79086 45 12V32C45 34.2091 43.2091 36 41 36H7C4.79086 36 3 34.2091 3 32V6Z"
                        fill="#1d6d46"
                    />
                    <rect x="7" y="9" width="34" height="10" rx="2" fill="#dcefe5" />
                    <path
                        d="M3 15C3 13.3431 4.34315 12 6 12H43C44.8128 12 46.2089 13.5973 45.9702 15.3946L43.5702 33.3946C43.3728 34.8752 42.1095 36 40.6162 36H6C4.34315 36 3 34.6569 3 33V15Z"
                        fill="#2d8659"
                    />
                </svg>
                <span class="text-2xl font-bold tracking-tight text-[#1a2520] dark:text-surface-0">
                    {{ appName }}
                </span>
            </div>

            <!-- Hero Copy & Illustration -->
            <div class="relative z-10 my-auto pt-6">
                <div class="max-w-md">
                    <h1 class="text-4xl xl:text-[2.75rem] font-extrabold tracking-tight text-[#18221d] dark:text-surface-0 leading-[1.18] mb-4">
                        Set New Password
                    </h1>
                    <p class="text-lg text-[#4a5d53] dark:text-surface-300 leading-relaxed">
                        Choose a strong password to keep<br />
                        your documents and workspace secure.
                    </p>
                </div>

                <!-- Shield + Padlock Security Vector Illustration -->
                <div class="mt-8 flex justify-start">
                    <svg
                        class="w-full max-w-[400px] h-auto drop-shadow-sm select-none"
                        viewBox="0 0 420 280"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <ellipse cx="210" cy="242" rx="120" ry="16" fill="#bfe2cf" class="dark:fill-emerald-900/40" />
                        <path
                            d="M210 34L312 72V144C312 204 268 242 210 258C152 242 108 204 108 144V72L210 34Z"
                            fill="#ffffff"
                            stroke="#93c4ab"
                            stroke-width="4"
                        />
                        <path
                            d="M210 52L294 84V142C294 192 258 224 210 238C162 224 126 192 126 142V84L210 52Z"
                            fill="#eaf7f0"
                        />
                        <!-- Padlock -->
                        <path
                            d="M180 132V112C180 95.4315 193.431 82 210 82C226.569 82 240 95.4315 240 112V132"
                            stroke="#1f6f46"
                            stroke-width="12"
                            stroke-linecap="round"
                        />
                        <rect x="164" y="130" width="92" height="68" rx="14" fill="#2d8659" />
                        <circle cx="210" cy="158" r="9" fill="#eaf7f0" />
                        <rect x="206" y="164" width="8" height="16" rx="4" fill="#eaf7f0" />
                    </svg>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="relative z-10 text-xs text-[#5d7468] dark:text-surface-400">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
            </div>
        </div>

        <!-- Right Column: Reset Password Form -->
        <div class="relative flex flex-col justify-center items-center px-6 py-12 sm:px-12 lg:px-20 bg-[#fcfdfc] dark:bg-surface-950 transition-colors duration-200">
            <!-- Theme Toggle Button (Top Right) -->
            <button
                type="button"
                @click="toggleDarkMode"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                class="absolute top-6 right-6 flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-surface-800 bg-white dark:bg-surface-900 text-slate-600 dark:text-surface-300 hover:bg-slate-50 dark:hover:bg-surface-800 text-xs font-medium shadow-sm transition-all cursor-pointer"
            >
                <i :class="[isDark ? 'pi pi-sun text-amber-400' : 'pi pi-moon text-slate-600']"></i>
                <span>{{ isDark ? 'Light' : 'Dark' }}</span>
            </button>

            <div class="w-full max-w-[400px]">
                <!-- Mobile Brand Logo (Visible only on small screens) -->
                <div class="lg:hidden flex items-center gap-2.5 mb-8">
                    <svg class="w-9 h-7 shrink-0" viewBox="0 0 48 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M3 6C3 3.79086 4.79086 2 7 2H17.3431C18.404 2 19.4214 2.42143 20.1716 3.17157L23.8284 6.82843C24.5786 7.57857 25.596 8 26.6569 8H41C43.2091 8 45 9.79086 45 12V32C45 34.2091 43.2091 36 41 36H7C4.79086 36 3 34.2091 3 32V6Z"
                            fill="#1d6d46"
                        />
                        <rect x="7" y="9" width="34" height="10" rx="2" fill="#dcefe5" />
                        <path
                            d="M3 15C3 13.3431 4.34315 12 6 12H43C44.8128 12 46.2089 13.5973 45.9702 15.3946L43.5702 33.3946C43.3728 34.8752 42.1095 36 40.6162 36H6C4.34315 36 3 34.6569 3 33V15Z"
                            fill="#2d8659"
                        />
                    </svg>
                    <span class="text-xl font-bold text-[#18221d] dark:text-surface-0">{{ appName }}</span>
                </div>

                <!-- Title Section -->
                <div class="mb-7">
                    <h2 class="text-3xl font-bold tracking-tight text-[#18221d] dark:text-surface-0">
                        Reset Your Password
                    </h2>
                    <p class="text-base text-slate-500 dark:text-surface-400 mt-2">
                        Enter your new password below to complete the reset.
                    </p>
                </div>

                <!-- Form Reset Password -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div class="flex flex-col gap-2">
                        <label for="email" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            Email Address
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-envelope text-slate-400" />
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="you@company.com"
                                autocomplete="username"
                                required
                                fluid
                                :invalid="!!form.errors.email"
                                class="!rounded-xl !py-2.5 !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46]"
                            />
                        </IconField>
                        <small v-if="form.errors.email" class="text-red-500 text-xs">
                            {{ form.errors.email }}
                        </small>
                    </div>

                    <!-- New Password Field -->
                    <div class="flex flex-col gap-2">
                        <label for="password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            New Password
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                            <Password
                                id="password"
                                v-model="form.password"
                                toggleMask
                                placeholder="Enter new password"
                                autocomplete="new-password"
                                autofocus
                                required
                                fluid
                                :invalid="!!form.errors.password"
                                inputClass="!pl-10 !py-2.5 !rounded-xl !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46] w-full"
                                class="w-full"
                            />
                        </IconField>
                        <small v-if="form.errors.password" class="text-red-500 text-xs">
                            {{ form.errors.password }}
                        </small>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="flex flex-col gap-2">
                        <label for="password_confirmation" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            Confirm Password
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                            <Password
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :feedback="false"
                                toggleMask
                                placeholder="Confirm new password"
                                autocomplete="new-password"
                                required
                                fluid
                                :invalid="!!form.errors.password_confirmation"
                                inputClass="!pl-10 !py-2.5 !rounded-xl !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46] w-full"
                                class="w-full"
                            />
                        </IconField>
                        <small v-if="form.errors.password_confirmation" class="text-red-500 text-xs">
                            {{ form.errors.password_confirmation }}
                        </small>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        label="Reset Password"
                        icon="pi pi-check-circle"
                        iconPos="left"
                        fluid
                        class="!mt-6 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !py-3 !rounded-xl shadow-sm transition-all"
                        :loading="form.processing"
                    />
                </form>

                <!-- Back to Login Link -->
                <div class="mt-7 text-center">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-[#1f6f46] hover:text-[#165334] dark:text-emerald-400 transition-colors"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                        <span>Back to Login</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
