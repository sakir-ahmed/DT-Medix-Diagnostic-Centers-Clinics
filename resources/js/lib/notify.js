import { toast } from 'vue-sonner';

const DURATIONS = { success: 3000, warning: 6000, info: 4000, error: 8000 };
const recent = new Map();

function show(type, title, options = {}) {
    const key = `${type}:${title}`;
    const now = Date.now();

    // Merge duplicates fired within one second.
    if (recent.has(key) && now - recent.get(key) < 1000) {
        return undefined;
    }
    recent.set(key, now);

    return toast[type](title, {
        duration: DURATIONS[type],
        description: options.description,
        action: options.action,
        id: options.id,
    });
}

/**
 * One wrapper around vue-sonner so every toast looks and behaves the same.
 */
export const notify = {
    success: (title, options) => show('success', title, options),
    error: (title, options) => show('error', title, options),
    warning: (title, options) => show('warning', title, options),
    info: (title, options) => show('info', title, options),
    promise: (promise, messages) => toast.promise(promise, messages),
    dismiss: (id) => toast.dismiss(id),
};
