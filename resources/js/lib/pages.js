import AdminLayout from '@/layouts/AdminLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import PlatformLayout from '@/layouts/PlatformLayout.vue';

/**
 * Resolve a page component and give it the default layout for its area.
 * Pages may set `defineOptions({ layout: ... })` to override (null = none).
 */
export function resolvePage(name, pages) {
    const importer = pages[`./pages/${name}.vue`];
    if (!importer) {
        throw new Error(`Page not found: ${name}`);
    }

    return (typeof importer === 'function' ? importer() : Promise.resolve(importer)).then((module) => {
        const page = module.default;
        if (page.layout === undefined) {
            if (name.startsWith('Admin/') || name === 'Error') {
                page.layout = AdminLayout;
            } else if (name.startsWith('Platform/')) {
                page.layout = PlatformLayout;
            } else if (name.startsWith('Auth/')) {
                page.layout = GuestLayout;
            }
        }
        return page;
    });
}
