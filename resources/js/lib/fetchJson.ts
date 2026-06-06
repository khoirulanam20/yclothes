import { getCsrfToken } from '@/lib/csrf';

export class FetchError extends Error {
    status: number;

    constructor(message: string, status: number) {
        super(message);
        this.status = status;
    }
}

export async function fetchJson<T>(url: string, init: RequestInit = {}): Promise<T> {
    const method = (init.method ?? 'GET').toUpperCase();
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(method !== 'GET' ? { 'X-XSRF-TOKEN': getCsrfToken() } : {}),
        ...(init.headers as Record<string, string> | undefined),
    };

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...init,
        headers,
    });

    if (!response.ok) {
        throw new FetchError(`Request gagal (${response.status})`, response.status);
    }

    return response.json() as Promise<T>;
}
