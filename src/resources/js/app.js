import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, ref } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ServerError from './Pages/Errors/ServerError.vue';

// PrimeVue
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';

const appName = import.meta.env.VITE_APP_NAME || 'DocPad';

if (typeof window !== 'undefined' && localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const hasClientCrash = ref(false);

        const vueApp = createApp({
            render: () => (hasClientCrash.value ? h(ServerError, { status: 500 }) : h(App, props)),
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark-mode',
                    },
                },
            })
            .use(ToastService)
            .directive('tooltip', Tooltip);

        vueApp.config.errorHandler = (err) => {
            if (import.meta.env.DEV) {
                console.error(err);
            }
            hasClientCrash.value = true;
        };

        window.addEventListener('error', () => {
            if (!el.children.length) {
                hasClientCrash.value = true;
            }
        });

        return vueApp.mount(el);
    },
    progress: {
        color: '#10b981',
    },
});
