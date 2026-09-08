import { createInertiaApp } from '@inertiajs/vue3';
import { ElConfigProvider } from 'element-plus';
import es from 'element-plus/es/locale/lang/es';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { vCan } from '@/directives/can';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

// Variables de tema (dark) de element-plus. Los estilos de cada componente
// se inyectan bajo demanda vía unplugin-vue-components (ver vite.config.ts).
import 'element-plus/theme-chalk/dark/css-vars.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({
            // `el-config-provider` fija el locale (es) para todo el árbol.
            render: () =>
                h(ElConfigProvider, { locale: es }, () => h(App, props)),
        });

        app.use(plugin);
        app.use(createPinia());

        app.directive('can', vCan);

        app.mount(el!);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
