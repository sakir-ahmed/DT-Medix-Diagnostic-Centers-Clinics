import './lib/icons';

import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from 'vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { i18nPlugin } from './lib/i18n';
import { resolvePage } from './lib/pages';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => (title ? `${title} - DT Medix` : 'DT Medix'),
        resolve: (name) => resolvePage(name, import.meta.glob('./pages/**/*.vue', { eager: true })),
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, { ...page.props.ziggy, location: new URL(page.props.ziggy?.location ?? 'http://localhost') })
                .use(i18nPlugin);
        },
    }),
);
