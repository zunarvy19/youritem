export class ApiError extends Error {
    public readonly status: number;
    public readonly errors: Record<string, string[]>;

    constructor(
        status: number,
        message: string,
        errors: Record<string, string[]> = {},
    ) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }

    get isValidationError(): boolean {
        return this.status === 422;
    }

    get isUnauthenticated(): boolean {
        return this.status === 401;
    }
}

const CSRF_COOKIE = 'XSRF-TOKEN';

function readCsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith(`${CSRF_COOKIE}=`));

    if (!match) {
        return '';
    }

    return decodeURIComponent(match.substring(CSRF_COOKIE.length + 1));
}

let csrfCookiePromise: Promise<void> | null = null;

export async function ensureCsrfCookie(): Promise<void> {
    csrfCookiePromise ??= fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
        headers: { Accept: 'application/json' },
    }).then(() => undefined);

    await csrfCookiePromise;
}

interface RequestOptions {
    method?: string;
    body?: unknown;
    query?: Record<string, string | number | boolean | undefined>;
}

export async function apiRequest<T>(
    path: string,
    options: RequestOptions = {},
): Promise<T> {
    const method = options.method ?? 'GET';
    const headers = new Headers({
        Accept: 'application/json',
    });

    let url = path;

    if (options.query) {
        const params = new URLSearchParams();
        Object.entries(options.query).forEach(([key, value]) => {
            if (value !== undefined) {
                params.set(key, String(value));
            }
        });
        const qs = params.toString();

        if (qs) {
            url += `?${qs}`;
        }
    }

    if (options.body !== undefined) {
        headers.set('Content-Type', 'application/json');
    }

    if (!['GET', 'HEAD'].includes(method)) {
        if (!readCsrfToken()) {
            await ensureCsrfCookie();
        }

        const token = readCsrfToken();

        if (token) {
            headers.set('X-XSRF-TOKEN', token);
        }
    }

    let response: Response;

    try {
        response = await fetch(url, {
            method,
            headers,
            credentials: 'include',
            body:
                options.body !== undefined
                    ? JSON.stringify(options.body)
                    : undefined,
        });
    } catch {
        throw new ApiError(
            0,
            'Network error. Please check your connection and try again.',
        );
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(
            response.status,
            (payload?.message as string) ??
                `Request failed with status ${response.status}.`,
            (payload?.errors as Record<string, string[]>) ?? {},
        );
    }

    return payload as T;
}
