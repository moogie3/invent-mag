import { describe, it, expect, beforeEach, vi } from 'vitest';

const importInitializer = () => import('@/js/admin/layouts/theme-initializer.js');

const matchMediaMock = (prefersDark) => {
    window.matchMedia = vi.fn().mockImplementation((query) => ({
        matches: query === '(prefers-color-scheme: dark)' && prefersDark,
        media: query,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        addListener: vi.fn(),
        removeListener: vi.fn(),
        dispatchEvent: vi.fn(),
    }));
};

describe('Theme initializer', () => {

    beforeEach(() => {
        vi.resetModules();
        localStorage.clear();
        document.documentElement.removeAttribute('data-bs-theme');
    });

    it('should apply dark theme from localStorage', async () => {
        // Arrange
        localStorage.setItem('theme', 'dark');

        // Act
        await importInitializer();

        // Assert
        expect(document.documentElement.getAttribute('data-bs-theme')).toBe('dark');
    });

    it('should apply light theme from localStorage', async () => {
        // Arrange
        localStorage.setItem('theme', 'light');

        // Act
        await importInitializer();

        // Assert
        expect(document.documentElement.getAttribute('data-bs-theme')).toBe('light');
    });

    it('should fall back to the OS dark preference when no theme is stored', async () => {
        // Arrange
        matchMediaMock(true);

        // Act
        await importInitializer();

        // Assert
        expect(document.documentElement.getAttribute('data-bs-theme')).toBe('dark');
    });

    it('should default to light when no theme is stored and no dark OS preference', async () => {
        // Arrange
        matchMediaMock(false);

        // Act
        await importInitializer();

        // Assert
        expect(document.documentElement.getAttribute('data-bs-theme')).toBe('light');
    });
});
