import { router } from '@inertiajs/vue3';

/**
 * Promise wrapper around an Inertia visit. Resolves with the page on success,
 * rejects with a readable message when validation fails or the server
 * flashed an error (business rule violations).
 */
export function visit(method, url, data = {}, options = {}) {
    return new Promise((resolve, reject) => {
        router.visit(url, {
            method,
            data,
            preserveScroll: true,
            preserveState: true,
            ...options,
            onSuccess: (page) => {
                const error = page.props.flash?.error;
                if (error) {
                    reject(new Error(error));
                    return;
                }
                resolve(page);
            },
            onError: (errors) => {
                const first = Object.values(errors ?? {})[0];
                reject(new Error(Array.isArray(first) ? first[0] : first));
            },
        });
    });
}

export const post = (url, data, options) => visit('post', url, data, options);
export const put = (url, data, options) => visit('put', url, data, options);
export const destroy = (url, data, options) => visit('delete', url, data, options);

/**
 * JSON GET for type-ahead endpoints (session auth, same origin).
 */
export async function getJson(url, params = {}) {
    const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== '')).toString();
    const response = await fetch(query ? `${url}?${query}` : url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });
    if (!response.ok) {
        throw new Error(`Request failed (${response.status})`);
    }

    return response.json();
}

export async function sendJson(method, url, data = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(url, {
        method: method.toUpperCase(),
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': token ?? '',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) {
        const message = body?.error?.message ?? body?.message ?? `Request failed (${response.status})`;
        const error = new Error(message);
        error.body = body;
        error.status = response.status;
        throw error;
    }

    return body;
}
