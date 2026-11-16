<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    team_name: '',
    team_code: '',
    description: '',
    company_email: '',
    requester_name: '',
    requester_email: '',
    password: '',
    password_confirmation: '',
});

const appName = import.meta.env.VITE_APP_NAME || 'DocPad';
const isDark = ref(false);
const confirmDialogVisible = ref(false);

const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark-mode', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark-mode');
});

const passwordMatch = computed(() => {
    if (!form.password_confirmation) return null;
    return form.password === form.password_confirmation;
});

const sanitizeTeamCode = () => {
    form.team_code = form.team_code.replace(/\s+/g, '-').toUpperCase();
};

const openConfirm = () => {
    if (passwordMatch.value === false) return;
    sanitizeTeamCode();
    confirmDialogVisible.value = true;
};

const submitConfirmed = () => {
    form.post(route('team-request.store'), {
        onError: () => {
            confirmDialogVisible.value = false;
        },
        onSuccess: () => {
            confirmDialogVisible.value = false;
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass = '!rounded-xl !py-2.5 !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46]';
const passwordInputClass = '!pl-10 !py-2.5 !rounded-xl !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46] w-full';
</script>

<template>
    <Head :title="t('auth.request_team_title')" />

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-white dark:bg-surface-950">
        <!-- Left Column: Branding -->
        <div class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-[#eaf7f0] dark:bg-emerald-950/40 px-14 pt-12 pb-10 border-r border-[#d8eee2] dark:border-surface-800">
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

            <!-- Hero Copy -->
            <div class="relative z-10 my-auto pt-6 max-w-md space-y-6">
                <div>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 dark:bg-emerald-950/70 px-3 py-1 text-xs font-semibold text-[#1f6f46] dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800">
                        <i class="pi pi-crown text-xs"></i>
                        Team Leader / PM Onboarding
                    </span>
                    <h1 class="mt-4 text-4xl xl:text-[2.6rem] font-extrabold tracking-tight text-[#18221d] dark:text-surface-0 leading-[1.18]">
                        Launch Your<br />
                        Team Workspace.
                    </h1>
                    <p class="mt-4 text-base text-[#4a5d53] dark:text-surface-300 leading-relaxed">
                        {{ t('auth.request_team_subtitle') }}
                    </p>
                </div>

                <div class="space-y-3 rounded-2xl bg-white/70 dark:bg-surface-900/60 border border-emerald-200/60 dark:border-emerald-900/40 p-5 backdrop-blur-sm">
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#1f6f46] text-white text-xs font-bold">1</span>
                        <p class="text-sm text-[#31453a] dark:text-surface-200">Propose your Team Name & unique Team Code.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#1f6f46] text-white text-xs font-bold">2</span>
                        <p class="text-sm text-[#31453a] dark:text-surface-200">Admin reviews & approves your request.</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#1f6f46] text-white text-xs font-bold">3</span>
                        <p class="text-sm text-[#31453a] dark:text-surface-200">Log in immediately as <strong>Team Leader / PM</strong> and invite your members.</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 text-xs text-[#5d7468] dark:text-surface-400">
                &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
            </div>
        </div>

        <!-- Right Column: Request Form -->
        <div class="relative flex flex-col justify-center items-center px-6 py-12 sm:px-12 lg:px-16 bg-[#fcfdfc] dark:bg-surface-950 transition-colors duration-200">
            <button
                type="button"
                @click="toggleDarkMode"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                class="absolute top-6 right-6 flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-surface-800 bg-white dark:bg-surface-900 text-slate-600 dark:text-surface-300 hover:bg-slate-50 dark:hover:bg-surface-800 text-xs font-medium shadow-sm transition-all cursor-pointer"
            >
                <i :class="[isDark ? 'pi pi-sun text-amber-400' : 'pi pi-moon text-slate-600']"></i>
                <span>{{ isDark ? 'Light' : 'Dark' }}</span>
            </button>

            <div class="w-full max-w-[440px]">
                <!-- Mobile Brand -->
                <div class="lg:hidden flex items-center gap-2.5 mb-6">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1f6f46] text-white">
                        <i class="pi pi-users text-sm"></i>
                    </span>
                    <span class="text-xl font-bold text-[#18221d] dark:text-surface-0">{{ appName }}</span>
                </div>

                <!-- Submitted Confirmation State -->
                <div
                    v-if="status === 'submitted'"
                    class="rounded-2xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/70 dark:bg-emerald-950/30 p-7 text-center space-y-4"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#1f6f46] text-white shadow-sm">
                        <i class="pi pi-check text-2xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-[#18221d] dark:text-surface-0">
                        {{ t('auth.request_team_success_title') }}
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                        {{ t('auth.request_team_success_desc') }}
                    </p>
                    <div class="pt-2 flex flex-col sm:flex-row gap-2.5 justify-center">
                        <Link :href="route('login')">
                            <Button
                                type="button"
                                :label="t('auth.login_button')"
                                icon="pi pi-sign-in"
                                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl !px-5"
                            />
                        </Link>
                        <Link :href="route('register')">
                            <Button
                                type="button"
                                :label="t('auth.register_button')"
                                outlined
                                class="!border-[#1f6f46] !text-[#1f6f46] dark:!border-emerald-400 dark:!text-emerald-400 !rounded-xl !px-5"
                            />
                        </Link>
                    </div>
                </div>

                <!-- Request Form -->
                <template v-else>
                    <div class="mb-6">
                        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-[#18221d] dark:text-surface-0">
                            {{ t('auth.request_team_heading') }}
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-surface-400 mt-1.5">
                            {{ t('auth.request_team_subtitle') }}
                        </p>
                    </div>

                    <form @submit.prevent="openConfirm" class="space-y-4">
                        <!-- Team Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div class="flex flex-col gap-1.5">
                                <label for="team_name" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                    Team Name <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    id="team_name"
                                    v-model="form.team_name"
                                    placeholder="e.g. Engineering"
                                    required
                                    fluid
                                    :invalid="!!form.errors.team_name"
                                    :class="inputClass"
                                />
                                <small v-if="form.errors.team_name" class="text-red-500 text-xs">{{ form.errors.team_name }}</small>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label for="team_code" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                    Desired Team Code <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    id="team_code"
                                    v-model="form.team_code"
                                    placeholder="e.g. ENG-01"
                                    required
                                    fluid
                                    :invalid="!!form.errors.team_code"
                                    :class="inputClass"
                                    @blur="sanitizeTeamCode"
                                />
                                <small v-if="form.errors.team_code" class="text-red-500 text-xs">{{ form.errors.team_code }}</small>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="description" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                Notes / Description <span class="font-normal text-slate-400">(Optional)</span>
                            </label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="2"
                                autoResize
                                placeholder="Team purpose (optional)"
                                fluid
                                class="!rounded-xl !py-2.5"
                            />
                            <small v-if="form.errors.description" class="text-red-500 text-xs">{{ form.errors.description }}</small>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="company_email" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                Company Email <span class="font-normal text-slate-400">(Optional)</span>
                            </label>
                            <IconField>
                                <InputIcon class="pi pi-building text-slate-400" />
                                <InputText
                                    id="company_email"
                                    v-model="form.company_email"
                                    type="email"
                                    placeholder="contact@company.com"
                                    fluid
                                    :invalid="!!form.errors.company_email"
                                    :class="inputClass"
                                />
                            </IconField>
                            <small v-if="form.errors.company_email" class="text-red-500 text-xs">{{ form.errors.company_email }}</small>
                        </div>

                        <div class="border-t border-slate-200/80 dark:border-surface-800 pt-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-surface-500 mb-3">
                                Team Leader / PM Account
                            </p>

                            <div class="space-y-3.5">
                                <div class="flex flex-col gap-1.5">
                                    <label for="requester_name" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                        {{ t('auth.name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <IconField>
                                        <InputIcon class="pi pi-user text-slate-400" />
                                        <InputText
                                            id="requester_name"
                                            v-model="form.requester_name"
                                            placeholder="Your full name"
                                            required
                                            fluid
                                            :invalid="!!form.errors.requester_name"
                                            :class="inputClass"
                                        />
                                    </IconField>
                                    <small v-if="form.errors.requester_name" class="text-red-500 text-xs">{{ form.errors.requester_name }}</small>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="requester_email" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                        {{ t('auth.email') }} <span class="text-red-500">*</span>
                                    </label>
                                    <IconField>
                                        <InputIcon class="pi pi-envelope text-slate-400" />
                                        <InputText
                                            id="requester_email"
                                            v-model="form.requester_email"
                                            type="email"
                                            placeholder="you@company.com"
                                            required
                                            fluid
                                            :invalid="!!form.errors.requester_email"
                                            :class="inputClass"
                                        />
                                    </IconField>
                                    <small v-if="form.errors.requester_email" class="text-red-500 text-xs">{{ form.errors.requester_email }}</small>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                    <div class="flex flex-col gap-1.5">
                                        <label for="password" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                            {{ t('auth.password') }} <span class="text-red-500">*</span>
                                        </label>
                                        <IconField>
                                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                                            <Password
                                                id="password"
                                                v-model="form.password"
                                                :feedback="false"
                                                toggleMask
                                                placeholder="Password"
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

                                    <div class="flex flex-col gap-1.5">
                                        <label for="password_confirmation" class="text-xs font-semibold text-slate-700 dark:text-surface-200">
                                            {{ t('auth.confirm_password') }} <span class="text-red-500">*</span>
                                        </label>
                                        <IconField>
                                            <InputIcon class="pi pi-lock text-slate-400 z-10" />
                                            <Password
                                                id="password_confirmation"
                                                v-model="form.password_confirmation"
                                                :feedback="false"
                                                toggleMask
                                                placeholder="Repeat password"
                                                autocomplete="new-password"
                                                required
                                                fluid
                                                :invalid="passwordMatch === false || !!form.errors.password_confirmation"
                                                :inputClass="passwordInputClass"
                                                class="w-full"
                                            />
                                        </IconField>
                                    </div>
                                </div>

                                <small
                                    v-if="passwordMatch === false"
                                    class="text-red-500 text-xs flex items-center gap-1.5"
                                >
                                    <i class="pi pi-times-circle"></i> Passwords do not match
                                </small>
                            </div>
                        </div>

                        <!-- Highlight Info Box -->
                        <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/70 bg-emerald-50/70 dark:bg-emerald-950/40 p-3.5 space-y-1.5 text-xs text-[#1f5137] dark:text-emerald-200">
                            <div class="flex items-start gap-2">
                                <i class="pi pi-crown text-[#1f6f46] dark:text-emerald-400 mt-0.5 shrink-0"></i>
                                <span>You will automatically be assigned as the <strong>Team Leader / PM</strong> for this team once approved.</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="pi pi-users text-[#1f6f46] dark:text-emerald-400 mt-0.5 shrink-0"></i>
                                <span>Default team capacity is <strong>5 members</strong> (can be adjusted later by requested to Admin).</span>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            :label="t('auth.request_team_button')"
                            icon="pi pi-send"
                            iconPos="left"
                            fluid
                            class="!mt-5 !bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !py-3 !rounded-xl shadow-sm transition-all"
                            :loading="form.processing"
                            :disabled="passwordMatch === false"
                        />
                    </form>

                    <div class="mt-6 text-center text-sm text-slate-500 dark:text-surface-400">
                        <Link
                            :href="route('register')"
                            class="font-semibold text-[#1f6f46] hover:text-[#165334] dark:text-emerald-400 transition-colors"
                        >
                            {{ t('auth.back_to_register') }}
                        </Link>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Confirm Submit Dialog -->
    <Dialog
        v-model:visible="confirmDialogVisible"
        modal
        :draggable="false"
        :style="{ width: '29rem' }"
        :breakpoints="{ '640px': '92vw' }"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eaf7f0] dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-400">
                    <i class="pi pi-exclamation-circle text-sm"></i>
                </span>
                <span class="font-bold text-base text-slate-800 dark:text-surface-0">
                    {{ t('auth.request_team_confirm_title') }}
                </span>
            </div>
        </template>

        <div class="space-y-3.5 pt-1 text-sm">
            <p class="text-slate-600 dark:text-surface-300 leading-relaxed">
                {{ t('auth.request_team_confirm_desc') }}
            </p>

            <div class="rounded-xl border border-slate-200 dark:border-surface-800 bg-slate-50/70 dark:bg-surface-900/60 p-3.5 space-y-2 text-xs">
                <div class="flex justify-between gap-3">
                    <span class="text-slate-500 dark:text-surface-400">Team Name</span>
                    <span class="font-semibold text-slate-800 dark:text-surface-0 text-right">{{ form.team_name }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-slate-500 dark:text-surface-400">Team Code</span>
                    <code class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-[#1f6f46] dark:text-emerald-300 font-semibold">
                        {{ form.team_code }}
                    </code>
                </div>
                <div v-if="form.company_email" class="flex justify-between gap-3">
                    <span class="text-slate-500 dark:text-surface-400">Company Email</span>
                    <span class="font-medium text-slate-700 dark:text-surface-200 text-right">{{ form.company_email }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-slate-500 dark:text-surface-400">Team Leader / PM</span>
                    <span class="font-semibold text-slate-800 dark:text-surface-0 text-right">
                        {{ form.requester_name }} ({{ form.requester_email }})
                    </span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-slate-500 dark:text-surface-400">Max Members</span>
                    <span class="font-medium text-slate-700 dark:text-surface-200">5 (Default)</span>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-2 pt-2">
                <Button
                    type="button"
                    :label="t('common.cancel')"
                    severity="secondary"
                    variant="outlined"
                    :disabled="form.processing"
                    @click="confirmDialogVisible = false"
                />
                <Button
                    type="button"
                    :label="t('auth.request_team_confirm_submit')"
                    icon="pi pi-send"
                    :loading="form.processing"
                    class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white"
                    @click="submitConfirmed"
                />
            </div>
        </template>
    </Dialog>
</template>
