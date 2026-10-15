import { reactive } from 'vue';

/**
 * Shared state for the single ConfirmHost mounted in the layouts.
 */
export const confirmState = reactive({
    open: false,
    options: {},
    resolve: null,
    processing: false,
    error: null,
});

/**
 * Promise-based confirmation:
 *   const result = await confirm({ variant: 'danger', level: 'reason', title, message, confirmText, reasons });
 *   if (result.confirmed) { ... result.reason, result.note, result.pin, result.login }
 *
 * Pass `action: async (result) => {}` to keep the dialog open with a spinner
 * while the request runs; a thrown error is shown inside the dialog.
 */
export function useConfirm() {
    return (options) =>
        new Promise((resolve) => {
            if (confirmState.resolve) {
                confirmState.resolve({ confirmed: false });
            }
            confirmState.options = {
                variant: 'danger',
                level: 'simple',
                reasons: [],
                facts: [],
                ...options,
            };
            confirmState.error = null;
            confirmState.processing = false;
            confirmState.resolve = resolve;
            confirmState.open = true;
        });
}
