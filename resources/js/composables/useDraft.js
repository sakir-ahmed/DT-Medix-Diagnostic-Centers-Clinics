import { onMounted, watch } from 'vue';
import { notify } from '@/lib/notify';

/**
 * Autosave a local draft of a long form so a refresh does not lose work.
 * Restores the draft on mount (with a toast) and clears it after saving.
 */
export function useDraft(key, form, { t } = {}) {
    const storageKey = `draft:${key}`;

    onMounted(() => {
        try {
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                Object.assign(form, JSON.parse(saved));
                notify.info(t ? t('drafts.restored') : 'Draft restored');
            }
        } catch {
            // Ignore broken or unavailable storage.
        }
    });

    let timer = null;
    watch(
        () => form.data(),
        (data) => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(data));
                } catch {
                    // Storage full or blocked.
                }
            }, 800);
        },
        { deep: true },
    );

    return {
        clear: () => {
            clearTimeout(timer);
            try {
                localStorage.removeItem(storageKey);
            } catch {
                // Ignore.
            }
        },
    };
}
