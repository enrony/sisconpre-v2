import { createInertiaApp } from '@inertiajs/vue3';
import ElementPlus from 'element-plus';
import es from 'element-plus/es/locale/lang/es';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import { vCan } from '@/directives/can';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

import 'element-plus/dist/index.css';
import 'element-plus/theme-chalk/dark/css-vars.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(createPinia());
        app.use(ElementPlus, { locale: es });

        for (const [name, icon] of Object.entries(ElementPlusIconsVue)) {
            app.component(name, icon as DefineComponent);
        }

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
