import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();
    const permissions = computed(() => page.props.auth?.permissions ?? []);
    const modules = computed(() => page.props.tenant?.modules ?? []);

    const can = (permission) => permissions.value.includes(permission);
    const hasModule = (module) => modules.value.includes(module);

    return { can, hasModule, permissions };
}
