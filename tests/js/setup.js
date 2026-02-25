import { vi } from 'vitest';

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
