/**
 * Thin JSON client for the mobile PWA.
 *
 * The shells under /m render no data server-side: every number comes from
 * /api/v1/mobile/*, authenticated with a Sanctum bearer token kept in
 * localStorage. A 401 means the token was revoked or expired, so the only
 * sane reaction is to drop it and go back to the login shell.
 */

const TOKEN_KEY = 'invent_mag_token';
const FLASH_KEY = 'invent_mag_flash';

/** Message text the JS client needs, rendered server-side so it is translated. */
export function msg(name, fallback) {
    return document.body?.dataset?.[name] || fallback;
}

/**
 * Remember why we were bounced back to the login shell. Stored in
 * sessionStorage (not localStorage) so it is consumed exactly once and does
 * not survive into the next visit.
 */
export function setFlash(message) {
    try {
        sessionStorage.setItem(FLASH_KEY, message);
    } catch {
        // Storage unavailable: the next visit simply starts without a reason.
    }
}

export function consumeFlash() {
    try {
        const message = sessionStorage.getItem(FLASH_KEY);
        if (message) {
            sessionStorage.removeItem(FLASH_KEY);
        }
        return message;
    } catch {
        return null;
    }
}

export class ApiError extends Error {
    constructor(message, status = 0, data = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
}

export function getToken() {
    try {
        return localStorage.getItem(TOKEN_KEY);
    } catch {
        return null;
    }
}

export function setToken(token) {
    try {
        localStorage.setItem(TOKEN_KEY, token);
    } catch {
        // Private mode: the session simply will not survive a reload.
    }
}

export function clearToken() {
    try {
        localStorage.removeItem(TOKEN_KEY);
    } catch {
        // Nothing to clean up if storage is unavailable.
    }
}

export function isAuthed() {
    return Boolean(getToken());
}

export function logout() {
    clearToken();
    window.location.assign('/m/login');
}

/**
 * @param {string} path
 * @param {{method?: string, body?: object|string, redirectOn401?: boolean}} [options]
 * @returns {Promise<any>}
 */
export async function api(path, options = {}) {
    const { body, redirectOn401 = true, ...rest } = options;
    const headers = { Accept: 'application/json', ...(rest.headers || {}) };

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    let payload = body;
    if (body && typeof body !== 'string') {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(path, { ...rest, headers, body: payload });
    } catch {
        throw new ApiError(msg('msgNetwork', 'Network error. Check your connection and try again.'), 0);
    }

    if (response.status === 401 && redirectOn401) {
        clearToken();
        // Leave the reason behind so the login shell can explain the bounce
        // instead of dropping the user on a blank form with no context.
        setFlash(msg('msgSessionExpired', 'Your session expired. Please sign in again.'));
        window.location.assign('/m/login');
        throw new ApiError(msg('msgSessionExpired', 'Session expired.'), 401);
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const message =
            (data && (data.message || data.error)) ||
            (data && data.errors && Object.values(data.errors).flat()[0]) ||
            `Request failed (${response.status})`;
        throw new ApiError(message, response.status, data);
    }

    return data;
}

/** POST /api/login → { token, token_type } */
export function login(email, password) {
    return api('/api/login', {
        method: 'POST',
        body: { email, password },
        redirectOn401: false,
    });
}

/** Redirect to the login shell unless a token is already stored. */
export function requireAuth() {
    if (!isAuthed()) {
        window.location.replace('/m/login');
        return false;
    }
    return true;
}
