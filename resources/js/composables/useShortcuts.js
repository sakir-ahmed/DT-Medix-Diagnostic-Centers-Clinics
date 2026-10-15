import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Register keyboard shortcuts for a page: { '/': fn, F2: fn, 'ctrl+k': fn }.
 */
export function useShortcuts(map) {
    const handler = (event) => {
        const target = event.target;
        const typing = target instanceof HTMLElement && (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable);
        const combo = `${event.ctrlKey || event.metaKey ? 'ctrl+' : ''}${event.key.length === 1 ? event.key.toLowerCase() : event.key}`;
        const action = map[combo];

        if (!action) {
            return;
        }
        if (typing && event.key.length === 1 && !event.ctrlKey && !event.metaKey) {
            return;
        }
        event.preventDefault();
        action(event);
    };

    onMounted(() => window.addEventListener('keydown', handler));
    onBeforeUnmount(() => window.removeEventListener('keydown', handler));
}
