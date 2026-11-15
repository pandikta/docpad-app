<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

const props = defineProps({
    visible: { type: Boolean, default: false },
    email: { type: String, default: '' },
});

const emit = defineEmits(['relogged-in']);

const password = ref('');
const errorMessage = ref('');
const submitting = ref(false);
const loggingOut = ref(false);

watch(
    () => props.visible,
    (isOpen) => {
        if (isOpen) {
            password.value = '';
            errorMessage.value = '';
        }
    },
);

const updateMetaCsrfToken = (token) => {
    if (!token || typeof document === 'undefined') return;
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', token);
    }
    if (window.axios?.defaults?.headers?.common) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
};

const submit = async () => {
    if (!password.value || submitting.value) return;

    submitting.value = true;
    errorMessage.value = '';

    try {
        const { data: csrfData } = await window.axios.get(route('relogin.csrf'));
        const { data } = await window.axios.post(
            route('relogin'),
            {
                email: props.email,
                password: password.value,
            },
            {
                headers: {
                    'X-CSRF-TOKEN': csrfData.csrf_token,
                },
            },
        );

        updateMetaCsrfToken(data.csrf_token);
        password.value = '';
        emit('relogged-in');
    } catch (error) {
        errorMessage.value =
            error?.response?.data?.message ||
            error?.response?.data?.errors?.password?.[0] ||
            'Failed to verify password. Please try again.';
    } finally {
        submitting.value = false;
    }
};

const logout = async () => {
    if (loggingOut.value) return;
    loggingOut.value = true;

    try {
        const { data: csrfData } = await window.axios.get(route('relogin.csrf'));
        await window.axios.post(
            route('logout'),
            {},
            {
                headers: {
                    'X-CSRF-TOKEN': csrfData.csrf_token,
                },
            },
        );
    } catch {
        // Session mungkin sudah habis; tetap arahkan ke halaman login.
    } finally {
        window.location.href = route('login');
    }
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
        :style="{ width: '28rem' }"
        :breakpoints="{ '640px': '92vw' }"
        :pt="{
            mask: { class: '!backdrop-blur-sm !bg-slate-900/50' },
        }"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/70 dark:border-emerald-800/60 flex items-center justify-center shrink-0">
                    <i class="pi pi-lock text-[#1f6f46] dark:text-emerald-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800 dark:text-surface-0 leading-tight">
                        Session Expired
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-surface-400 mt-0.5">
                        Re-authenticate to continue working
                    </p>
                </div>
            </div>
        </template>

        <form id="relogin-form" class="space-y-4 pt-1" @submit.prevent="submit">
            <p class="text-sm text-slate-600 dark:text-surface-300 leading-relaxed">
                Sorry, your access session has expired. Please enter your password to unlock your session.
            </p>

            <div
                v-if="email"
                class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-50 dark:bg-surface-800/70 border border-slate-200/80 dark:border-surface-700 text-xs text-slate-600 dark:text-surface-300"
            >
                <i class="pi pi-user text-[#1f6f46] dark:text-emerald-400 text-xs"></i>
                <span class="font-medium truncate">{{ email }}</span>
            </div>

            <div class="flex flex-col gap-2">
                <label for="relogin-password" class="text-sm font-semibold text-slate-700 dark:text-surface-200">
                    Password
                </label>
                <IconField>
                    <InputIcon class="pi pi-lock text-slate-400 z-10" />
                    <Password
                        inputId="relogin-password"
                        v-model="password"
                        :feedback="false"
                        toggleMask
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        autofocus
                        fluid
                        :invalid="!!errorMessage"
                        inputClass="!pl-10 !py-2.5 !rounded-xl !border-slate-200 dark:!border-surface-700 focus:!border-[#1f6f46] w-full"
                        class="w-full"
                        @keydown.enter.prevent="submit"
                    />
                </IconField>
                <small v-if="errorMessage" class="text-red-500 text-xs">
                    {{ errorMessage }}
                </small>
            </div>
        </form>

        <template #footer>
            <Button
                label="Logout"
                icon="pi pi-sign-out"
                severity="secondary"
                variant="outlined"
                :loading="loggingOut"
                :disabled="submitting"
                class="!rounded-xl"
                @click="logout"
            />
            <Button
                type="submit"
                form="relogin-form"
                label="Unlock Session"
                icon="pi pi-unlock"
                :loading="submitting"
                :disabled="!password || loggingOut"
                class="!bg-[#1f6f46] hover:!bg-[#185a38] !border-[#1f6f46] !text-white !font-semibold !rounded-xl"
            />
        </template>
    </Dialog>
</template>
