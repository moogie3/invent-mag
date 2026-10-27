import { vi } from 'vitest';

// jsdom exposes `localStorage` as a getter on the Window prototype, so when
// vitest copies the window's own keys onto globalThis it never lands there —
// `window` IS `globalThis` in this environment, so tests read `undefined`.
// Install a real per-process Storage implementation instead of letting
// theme/POS tests fail on an environment gap.
if (typeof globalThis.localStorage === 'undefined') {
    const store = new Map();
    const storage = {
        getItem: (key) => (store.has(String(key)) ? store.get(String(key)) : null),
        setItem: (key, value) => { store.set(String(key), String(value)); },
        removeItem: (key) => { store.delete(String(key)); },
        clear: () => { store.clear(); },
        key: (index) => Array.from(store.keys())[index] ?? null,
        get length() { return store.size; },
    };
    vi.stubGlobal('localStorage', storage);
}

// Mock the global bootstrap object
vi.stubGlobal('bootstrap', {
  Modal: class {
    constructor() {}
    show = vi.fn();
    hide = vi.fn();
  },
});

// Create a mock for the CSRF token meta tag
const csrfToken = document.createElement('meta');
csrfToken.name = 'csrf-token';
csrfToken.content = 'mock-csrf-token';
document.head.appendChild(csrfToken);

// Mock the global fetch function to avoid real network requests in tests
vi.spyOn(window, 'fetch').mockImplementation(() => {
    return Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ 
            theme_mode: 'dark',
            system_language: 'en'
        }),
    });
});
