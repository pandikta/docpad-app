<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Message from 'primevue/message';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
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
    form.post(route('password.email'));
};
</script>

<template>
    <Head :title="t('auth.forgot_title')" />

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
                        Forgot Password?
                    </h1>
                    <p class="text-lg text-[#4a5d53] dark:text-surface-300 leading-relaxed">
                        No worries! Enter your email address<br />
                        and we'll send you a reset link.
                    </p>
                </div>

                <!-- Envelope + Paper Plane + Dashed Trail Vector Illustration -->
                <div class="mt-6 flex justify-start">
                    <svg
                        class="w-full max-w-[440px] h-auto drop-shadow-sm select-none"
                        viewBox="0 0 460 300"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <!-- Dashed Curved Flight Path -->
                        <path
                            d="M115 165C52 182 32 232 95 252C168 275 268 225 322 128"
                            stroke="#689e85"
                            stroke-width="2.5"
                            stroke-dasharray="7 7"
                            stroke-linecap="round"
                            fill="none"
                        />

                        <!-- Soft Shadow Under Envelope -->
                        <ellipse
                            cx="188"
                            cy="218"
                            rx="92"
                            ry="12"
                            transform="rotate(-11 188 218)"
                            fill="#b7decb"
                            class="opacity-65 dark:opacity-15"
                        />

                        <!-- Tilted Green Envelope -->
                        <g transform="translate(86, 96) rotate(-11)">
                            <!-- Envelope Base Body -->
                            <rect
                                x="0"
                                y="0"
                                width="176"
                                height="114"
                                rx="12"
                                fill="#2c8f5e"
                                stroke="#1c6843"
                                stroke-width="2"
                            />
                            <!-- Left Inner Fold Triangle -->
                            <path
                                d="M3 8L82 62L3 108V8Z"
                                fill="#369d6a"
                            />
                            <!-- Right Inner Fold Triangle -->
                            <path
                                d="M173 8L94 62L173 108V8Z"
                                fill="#267d52"
                            />
                            <!-- Bottom Flap -->
                            <path
                                d="M4 109L79 54C84.5 50 91.5 50 97 54L172 109C170 112 166 113 162 113H14C10 113 6 112 4 109Z"
                                fill="#3ca973"
                                stroke="#23754c"
                                stroke-width="1.5"
                            />
                            <!-- Top Flap Shadow -->
                            <path
                                d="M4 6L80 65C84.8 68.6 91.2 68.6 96 65L172 6"
                                fill="#1d6843"
                                opacity="0.28"
                            />
                            <!-- Top Closed Flap -->
                            <path
                                d="M3 9C3 4.5 7 1 12 1H164C169 1 173 4.5 173 9L96.5 63C91.4 66.6 84.6 66.6 79.5 63L3 9Z"
                                fill="#4bb982"
                                stroke="#1e6b45"
                                stroke-width="2"
                            />
                            <!-- Mint Highlight Along Top Flap V-Edge -->
                            <path
                                d="M8 10L80.5 61C85 64.2 91 64.2 95.5 61L168 10"
                                stroke="#8ae0b5"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                fill="none"
                            />
                        </g>

                        <!-- Soaring Green Paper Airplane -->
                        <g transform="translate(278, 36)">
                            <!-- Left/Top Wing -->
                            <path
                                d="M112 14L6 48L46 63L112 14Z"
                                fill="#56c18b"
                                stroke="#1c6843"
                                stroke-width="2"
                                stroke-linejoin="round"
                            />
                            <!-- Center Deep Fold (Underneath) -->
                            <path
                                d="M112 14L46 63L54 95L72 74L112 14Z"
                                fill="#1b6843"
                                stroke="#155335"
                                stroke-width="2"
                                stroke-linejoin="round"
                            />
                            <!-- Right/Lower Wing -->
                            <path
                                d="M112 14L62 72L92 86L112 14Z"
                                fill="#39a56f"
                                stroke="#1c6843"
                                stroke-width="2"
                                stroke-linejoin="round"
                            />
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="relative z-10 text-xs text-[#5d7468] dark:text-surface-400">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
            </div>
        </div>

        <!-- Right Column: Forgot Password Form -->
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
                        {{ t('auth.forgot_title') }}
                    </h2>
                    <p class="text-base text-slate-500 dark:text-surface-400 mt-2">
                        {{ t('auth.forgot_desc') }}
                    </p>
                </div>

                <!-- Success Status Message -->
                <Message v-if="status" severity="success" :closable="false" class="mb-5">
                    {{ status }}
                </Message>

                <!-- Form Forgot Password -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
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
                                autofocus
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

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        :label="t('auth.send_reset_link')"
                        icon="pi pi-send"
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
