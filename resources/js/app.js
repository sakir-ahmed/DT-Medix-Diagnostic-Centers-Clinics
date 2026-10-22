import 'vue-sonner/style.css';
import '../css/app.css';
import './lib/icons';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { i18nPlugin } from './lib/i18n';
import { resolvePage } from './lib/pages';

createInertiaApp({
    title: (title) => (title && ! title.includes('DT Medix') ? `${title} - DT Medix` : (title || 'DT Medix')),
    resolve: (name) => resolvePage(name, import.meta.glob('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nPlugin)
            .mount(el);
    },
    progress: {
        color: '#0d9488',
    },
});
