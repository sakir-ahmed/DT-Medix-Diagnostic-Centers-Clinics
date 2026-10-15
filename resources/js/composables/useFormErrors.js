import { nextTick } from 'vue';

/**
 * Scroll to and focus the first invalid field after a failed submit.
 * Fields are found by id (FormField uses the field name as id).
 */
export async function focusFirstError(errors) {
    await nextTick();
    const keys = Object.keys(errors ?? {});
    if (keys.length === 0) {
        return;
    }
    const candidates = keys
        .map((key) => document.getElementById(key) ?? document.querySelector(`[name="${key}"]`))
        .filter(Boolean)
        .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
    const first = candidates[0];
    if (first) {
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
        first.focus({ preventScroll: true });
    }
}

/**
 * Standard submit options: keep input, focus first error, toast on outcome.
 */
export function submitOptions({ onSuccess, onError, preserveScroll = true } = {}) {
    return {
        preserveScroll,
        onError: (errors) => {
            focusFirstError(errors);
            onError?.(errors);
        },
        onSuccess,
    };
}
