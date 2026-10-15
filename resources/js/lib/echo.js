import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let instance = null;

/**
 * Lazily connect to Reverb. Returns null when real-time is not configured,
 * so pages fall back to polling. Channel payloads contain IDs only.
 */
export function echo() {
    if (instance || typeof window === 'undefined') {
        return instance;
    }
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        return null;
    }
    window.Pusher = Pusher;
    instance = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return instance;
}
