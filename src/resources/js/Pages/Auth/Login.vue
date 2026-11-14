<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

defineProps({
    status: {
        type: String,
    },
    canResetPassword: {
        type: Boolean,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
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
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In" />

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
                    <!-- Folder Back -->
                    <path
                        d="M3 6C3 3.79086 4.79086 2 7 2H17.3431C18.404 2 19.4214 2.42143 20.1716 3.17157L23.8284 6.82843C24.5786 7.57857 25.596 8 26.6569 8H41C43.2091 8 45 9.79086 45 12V32C45 34.2091 43.2091 36 41 36H7C4.79086 36 3 34.2091 3 32V6Z"
                        fill="#1d6d46"
                    />
                    <!-- Inner Paper Peek -->
                    <rect x="7" y="9" width="34" height="10" rx="2" fill="#dcefe5" />
                    <!-- Folder Front Flap -->
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
                    <h1 class="text-4xl xl:text-[2.75rem] font-extrabold tracking-tight text-[#18221d] dark:text-surface-0 leading-[1.18] mb-5">
                        Share. Organize.<br />
                        Work Together.
                    </h1>
                    <p class="text-lg text-[#4a5d53] dark:text-surface-300 leading-relaxed">
                        A simple workspace for your team<br />
                        to manage documents together.
                    </p>
                </div>

                <!-- Folder + Document + Cloud Upload Vector Illustration -->
                <div class="mt-6 flex justify-start">
                    <svg
                        class="w-full max-w-[440px] h-auto drop-shadow-sm select-none"
                        viewBox="0 0 460 310"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <!-- Ground Shadow Ellipse -->
                        <ellipse cx="215" cy="276" rx="135" ry="14" fill="#b7decb" class="opacity-70 dark:opacity-15" />
                        <ellipse cx="215" cy="276" rx="95" ry="9" fill="#9fd1b8" class="opacity-60 dark:opacity-15" />

                        <!-- Left Leafy Stem / Plant -->
                        <g>
                            <!-- Main Stem -->
                            <path
                                d="M116 262C102 225 84 182 68 138"
                                stroke="#5ab889"
                                stroke-width="3.5"
                                stroke-linecap="round"
                            />
                            <!-- Top Tip Leaf -->
                            <path
                                d="M68 138C56 122 52 106 60 102C68 98 76 116 68 138Z"
                                fill="#6bc497"
                            />
                            <!-- Left Leaves -->
                            <path
                                d="M77 162C56 156 40 144 44 135C48 126 68 139 77 162Z"
                                fill="#6bc497"
                            />
                            <path
                                d="M89 194C64 190 46 178 50 168C54 158 77 170 89 194Z"
                                fill="#6bc497"
                            />
                            <path
                                d="M101 226C76 224 58 214 62 204C66 194 88 204 101 226Z"
                                fill="#6bc497"
                            />
                            <!-- Right Leaves -->
                            <path
                                d="M76 156C82 135 95 122 103 127C111 132 96 148 76 156Z"
                                fill="#5ab889"
                            />
                            <path
                                d="M88 188C95 166 110 154 118 160C126 166 109 182 88 188Z"
                                fill="#5ab889"
                            />
                            <path
                                d="M100 220C108 200 122 190 129 196C136 202 120 216 100 220Z"
                                fill="#5ab889"
                            />
                        </g>

                        <!-- Folder Back Plate (Dark Green) -->
                        <path
                            d="M110 112C109 102 117 94 127 94H178C184 94 190 97 194 102L204 115C208 120 214 123 220 123H285C295 123 303 131 302 141L294 256C293 265 285 272 276 272H128C119 272 111 265 111 255L110 112Z"
                            fill="#1f6f47"
                            stroke="#155233"
                            stroke-width="2.5"
                        />

                        <!-- White Document Sheet Inside Folder -->
                        <g transform="rotate(2 215 135)">
                            <!-- Paper Body -->
                            <path
                                d="M158 46C158 39.3726 163.373 34 170 34H244L270 60V195C270 201.627 264.627 207 258 207H170C163.373 207 158 201.627 158 195V46Z"
                                fill="#ffffff"
                                stroke="#dcebe3"
                                stroke-width="2"
                            />
                            <!-- Folded Corner -->
                            <path
                                d="M244 34V52C244 56.4183 247.582 60 252 60H270L244 34Z"
                                fill="#d5e6dd"
                            />
                            <!-- Document Lines -->
                            <rect x="178" y="68" width="44" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="178" y="90" width="70" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="178" y="112" width="70" height="8" rx="4" fill="#b8ccc2" />
                            <rect x="178" y="134" width="56" height="8" rx="4" fill="#b8ccc2" />
                        </g>

                        <!-- Folder Front Flap (Mint Emerald Green) -->
                        <path
                            d="M122 134C121 124 129 116 139 116H194C200 116 206 119 210 124L218 133C222 138 228 141 235 141H304C314 141 322 150 320 160L300 258C298 266 291 272 282 272H129C120 272 113 265 113 256L122 134Z"
                            fill="#4eb883"
                            stroke="#1c6440"
                            stroke-width="2.5"
                        />
                        <!-- Subtle Inner Highlight Line on Front Flap -->
                        <path
                            d="M126 136C125.5 128 132 121 140 121H193C198 121 203 123.5 206.5 127.5L215 137.5C219 142 225 145.5 232 145.5H302C310 145.5 315.5 152 314 160"
                            stroke="#7cd3a7"
                            stroke-width="3"
                            stroke-linecap="round"
                            fill="none"
                        />

                        <!-- Cloud Upload Icon on Right -->
                        <g transform="translate(310, 82)">
                            <path
                                d="M34 78H92C106.359 78 118 66.3594 118 52C118 38.6851 107.988 27.7107 95.0751 26.1859C90.4785 11.0315 76.4046 0 59.75 0C39.4535 0 23 16.4535 23 36.75C23 37.8935 23.0522 39.025 23.1541 40.1419C13.9789 43.391 7.5 52.1489 7.5 62.5C7.5 71.0604 14.4396 78 23 78H34Z"
                                fill="#1b7046"
                            />
                            <!-- Upward Arrow Inside Cloud -->
                            <path
                                d="M63 63V30M63 30L49 44M63 30L77 44"
                                stroke="#ffffff"
                                stroke-width="7.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </g>
                    </svg>
                </div>
            </div>

            <!-- Subtle Spacer at Bottom -->
            <div class="relative z-10 text-xs text-[#5d7468] dark:text-surface-400">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
            </div>
        </div>

        <!-- Right Column: Login Form -->
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
                <div class="mb-8">
                    <h2 class="text-3xl font-bold tracking-tight text-[#18221d] dark:text-surface-0">
                        {{ appName }}
                    </h2>
                    <p class="text-base text-slate-500 dark:text-surface-400 mt-2">
                        Sign in to continue to your workspace
                    </p>
                </div>

                <!-- Status Message -->
                <Message v-if="status" severity="success" :closable="false" class="mb-5">
                    {{ status }}
                </Message>

                <!-- Form Login -->
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

                    <!-- Password Field -->
                    <div class="flex flex-col gap-2">
                        <label for="password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                            Password
                        </label>
                        <IconField>
                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                            <Password
                                id="password"
                                v-model="form.password"
                                :feedback="false"
                                toggleMask
                                placeholder="Enter your password"
                                autocomplete="current-password"
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

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-sm pt-0.5">
                        <div class="flex items-center gap-2.5">
                            <Checkbox
                                v-model="form.remember"
                                inputId="remember"
                                :binary="true"
                            />
                            <label for="remember" class="font-medium text-slate-600 dark:text-surface-300 cursor-pointer select-none">
                                Remember me
                            </label>
                        </div>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-semibold text-[#1f6f46] hover:text-[#165334] dark:text-emerald-400 transition-colors"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        label="Sign In"
                        icon="pi pi-arrow-right"
                        iconPos="left"
                        fluid
                        class="!mt-6 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !py-3 !rounded-xl shadow-sm transition-all"
                        :loading="form.processing"
                    />
                </form>

                <!-- Divider "or" -->
                <div class="relative flex items-center justify-center my-6">
                    <div class="border-t border-slate-200 dark:border-surface-800 w-full"></div>
                    <span class="bg-[#fcfdfc] dark:bg-surface-950 px-3 text-xs text-slate-400 dark:text-surface-500">
                        or
                    </span>
                    <div class="border-t border-slate-200 dark:border-surface-800 w-full"></div>
                </div>

                <!-- Register Button -->
                <Link :href="route('register')" class="block mb-6">
                    <Button
                        type="button"
                        label="Don't have an account yet?"
                        icon="pi pi-user-plus"
                        iconPos="left"
                        outlined
                        fluid
                        class="!border-[#1f6f46] !text-[#1f6f46] hover:!bg-emerald-50 dark:!border-emerald-400 dark:!text-emerald-400 dark:hover:!bg-emerald-950/40 !font-semibold !py-3 !rounded-xl transition-all"
                    />
                </Link>

                <!-- Security Footer Note -->
                <div class="text-center">
                    <p class="text-xs text-slate-500 dark:text-surface-400 flex items-center justify-center gap-2">
                        <i class="pi pi-shield text-[#1f6f46] dark:text-emerald-400 text-sm"></i>
                        Your data is secure with us
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
