<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const toast = useToast();

const form = useForm({
    team_code: '',
    name: '',
    email: '',
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

// Live check: password vs confirmation (null = belum diketik)
const passwordMatch = computed(() => {
    if (!form.password_confirmation) return null;
    return form.password === form.password_confirmation;
});

const submit = () => {
    if (passwordMatch.value === false) return;    

    form.post(route('register'), {
        onError: (errors) => {
            if (errors.team_code) {
                // toast.add({
                //     severity: 'error',
                //     summary: 'Team Not Found',
                //     detail: errors.team_code,
                //     life: 5000,
                // });
            }
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = '!rounded-xl !py-2.5 !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46]';
const passwordInputClass = '!pl-10 !py-2.5 !rounded-xl !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46] w-full';
</script>

<template>
    <Head :title="t('auth.register_title')" />

    <!-- Toast notification (top-center) -->
    <Toast position="top-center" />

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-white dark:bg-surface-950">
        <!-- Left Column: Branding & Illustration Area -->
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-[#eaf7f0] dark:bg-emerald-950/40 px-14 pt-12 pb-10 border-r border-[#d8eee2] dark:border-surface-800">
            <!-- Background Wavy Shapes -->
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
                        Join Your<br />
                        Team Workspace.
                    </h1>
                    <p class="text-lg text-[#4a5d53] dark:text-surface-300 leading-relaxed">
                        Create your account and start<br />
                        sharing documents in minutes.
                    </p>
                </div>

                <!-- Team Members + Document Cards Illustration -->
                <div class="mt-6 flex justify-start">
                    <svg
                        class="w-full max-w-[440px] h-auto drop-shadow-sm select-none"
                        viewBox="0 0 460 300"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <ellipse cx="225" cy="272" rx="150" ry="12" fill="#b7decb" class="opacity-65 dark:opacity-15" />

                        <!-- Back Document Card -->
                        <g transform="translate(250, 50) rotate(6)">
                            <rect width="130" height="165" rx="14" fill="#ffffff" stroke="#cfe5d9" stroke-width="2" />
                            <rect x="18" y="22" width="50" height="9" rx="4.5" fill="#9fcfb6" />
                            <rect x="18" y="46" width="94" height="8" rx="4" fill="#c9ddd2" />
                            <rect x="18" y="66" width="94" height="8" rx="4" fill="#c9ddd2" />
                            <rect x="18" y="86" width="70" height="8" rx="4" fill="#c9ddd2" />
                            <rect x="18" y="106" width="84" height="8" rx="4" fill="#c9ddd2" />
                        </g>

                        <!-- Front Document Card -->
                        <g transform="translate(95, 62) rotate(-5)">
                            <rect width="150" height="185" rx="14" fill="#ffffff" stroke="#bcdccb" stroke-width="2" />
                            <rect width="150" height="42" rx="14" fill="#2c8f5e" />
                            <rect y="28" width="150" height="14" fill="#2c8f5e" />
                            <circle cx="24" cy="21" r="7" fill="#ffffff" opacity="0.9" />
                            <rect x="40" y="16" width="60" height="9" rx="4.5" fill="#ffffff" opacity="0.9" />
                            <rect x="18" y="62" width="114" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="18" y="82" width="114" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="18" y="102" width="82" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="18" y="122" width="100" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="18" y="148" width="54" height="20" rx="10" fill="#dff3e8" stroke="#8fcaa9" />
                            <circle cx="30" cy="158" r="4" fill="#2c8f5e" />
                        </g>

                        <!-- Avatar Group -->
                        <g transform="translate(60, 205)">
                            <circle cx="24" cy="24" r="24" fill="#4eb883" stroke="#ffffff" stroke-width="4" />
                            <circle cx="24" cy="19" r="8" fill="#ffffff" />
                            <path d="M9 38C11 30 17 27 24 27C31 27 37 30 39 38" fill="#ffffff" />

                            <circle cx="62" cy="24" r="24" fill="#1f6f46" stroke="#ffffff" stroke-width="4" />
                            <circle cx="62" cy="19" r="8" fill="#ffffff" />
                            <path d="M47 38C49 30 55 27 62 27C69 27 75 30 77 38" fill="#ffffff" />

                            <circle cx="100" cy="24" r="24" fill="#7cd3a7" stroke="#ffffff" stroke-width="4" />
                            <circle cx="100" cy="19" r="8" fill="#ffffff" />
                            <path d="M85 38C87 30 93 27 100 27C107 27 113 30 115 38" fill="#ffffff" />
                        </g>

                        <!-- Plus Badge -->
                        <g transform="translate(345, 200)">
                            <circle cx="30" cy="30" r="30" fill="#1b7046" />
                            <path d="M30 16V44M16 30H44" stroke="#ffffff" stroke-width="7" stroke-linecap="round" />
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="relative z-10 text-xs text-[#5d7468] dark:text-surface-400">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
            </div>
        </div>

        <!-- Right Column: Register Form -->
        <div class="relative flex flex-col justify-center items-center px-6 py-12 sm:px-12 lg:px-20 bg-[#fcfdfc] dark:bg-surface-950 transition-colors duration-200">
            <!-- Theme Toggle Button -->
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
                <!-- Mobile Brand Logo -->
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
                        Create Account
                    </h2>
                    <p class="text-base text-slate-500 dark:text-surface-400 mt-2">
                        Fill in your details to get started.
                    </p>
                </div>

                <!-- Form Register -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Team Code -->
                    <div class="flex flex-col gap-2">
                        <label for="team_code" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            Team Code
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-users text-slate-400" />
                            <InputText
                                id="team_code"
                                v-model="form.team_code"
                                type="text"
                                placeholder="Enter your team code"
                                autocomplete="organization"
                                autofocus
                                required
                                fluid
                                :invalid="!!form.errors.team_code"
                                :class="inputClass"
                            />
                        </IconField>
                        <small v-if="form.errors.team_code" class="text-red-500 text-xs">{{ form.errors.team_code }}</small>
                        <small v-else class="text-slate-400 dark:text-surface-500 text-xs">
                            You'll join this team after registration.
                        </small>
                    </div>

                    <!-- Name -->
                    <div class="flex flex-col gap-2">
                        <label for="name" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            {{ t('auth.name') }}
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-user text-slate-400" />
                            <InputText
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter your name"
                                autocomplete="name"
                                required
                                fluid
                                :invalid="!!form.errors.name"
                                :class="inputClass"
                            />
                        </IconField>
                        <small v-if="form.errors.name" class="text-red-500 text-xs">{{ form.errors.name }}</small>
                    </div>

                    <!-- Email -->
                    <div class="flex flex-col gap-2">
                        <label for="email" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            {{ t('auth.email') }}
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
                                :class="inputClass"
                            />
                        </IconField>
                        <small v-if="form.errors.email" class="text-red-500 text-xs">{{ form.errors.email }}</small>
                    </div>

                    <!-- Password -->
                    <div class="flex flex-col gap-2">
                        <label for="password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            {{ t('auth.password') }}
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                            <Password
                                id="password"
                                v-model="form.password"
                                :feedback="false"
                                toggleMask
                                placeholder="Create a password"
                                autocomplete="new-password"
                                required
                                fluid
                                :invalid="!!form.errors.password"
                                :inputClass="passwordInputClass"
                                class="w-full"
                            />
                        </IconField>
                        <small v-if="form.errors.password" class="text-red-500 text-xs">{{ form.errors.password }}</small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="flex flex-col gap-2">
                        <label for="password_confirmation" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            {{ t('auth.confirm_password') }}
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                            <Password
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :feedback="false"
                                toggleMask
                                placeholder="Repeat your password"
                                autocomplete="new-password"
                                required
                                fluid
                                :invalid="passwordMatch === false || !!form.errors.password_confirmation"
                                :inputClass="passwordInputClass"
                                class="w-full"
                            />
                        </IconField>
                        <small
                            v-if="passwordMatch === true"
                            class="text-emerald-600 dark:text-emerald-400 text-xs flex items-center gap-1.5"
                        >
                            <i class="pi pi-check-circle"></i> Passwords match
                        </small>
                        <small
                            v-else-if="passwordMatch === false"
                            class="text-red-500 text-xs flex items-center gap-1.5"
                        >
                            <i class="pi pi-times-circle"></i> Passwords do not match
                        </small>
                        <small v-if="form.errors.password_confirmation" class="text-red-500 text-xs">
                            {{ form.errors.password_confirmation }}
                        </small>
                    </div>

                    <!-- Submit -->
                    <Button
                        type="submit"
                        :label="t('auth.register_button')"
                        icon="pi pi-user-plus"
                        iconPos="left"
                        fluid
                        class="!mt-6 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !py-3 !rounded-xl shadow-sm transition-all"
                        :loading="form.processing"
                        :disabled="passwordMatch === false"
                    />
                </form>

                <!-- Back to Login -->
                <div class="mt-7 text-center text-sm text-slate-500 dark:text-surface-400">
                    {{ t('auth.already_registered') }}
                    <Link
                        :href="route('login')"
                        class="font-semibold text-[#1f6f46] hover:text-[#165334] dark:text-emerald-400 transition-colors ml-1"
                    >
                        {{ t('auth.login_button') }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
