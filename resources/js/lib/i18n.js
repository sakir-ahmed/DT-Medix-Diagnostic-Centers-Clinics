import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

function lookup(translations, key) {
    return key.split('.').reduce((node, part) => (node && typeof node === 'object' ? node[part] : undefined), translations);
}

export function translate(translations, key, replacements = {}) {
    let value = lookup(translations ?? {}, key);

    if (typeof value !== 'string') {
        return key;
    }

    for (const [name, replacement] of Object.entries(replacements)) {
        value = value.replaceAll(`:${name}`, String(replacement ?? ''));
    }

    return value;
}

/**
 * Translation helper backed by the "translations" shared Inertia prop.
 */
export function useI18n() {
    const page = usePage();
    const locale = computed(() => page.props.app?.locale ?? 'en');

    const t = (key, replacements = {}) => translate(page.props.translations, key, replacements);
    const has = (key) => typeof lookup(page.props.translations ?? {}, key) === 'string';

    return { t, has, locale };
}

export const i18nPlugin = {
    install(app) {
        app.config.globalProperties.$t = (key, replacements = {}) => {
            const page = app.config.globalProperties.$page;

            return translate(page?.props?.translations, key, replacements);
        };
    },
};
