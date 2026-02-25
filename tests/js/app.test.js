import { describe, it, expect, beforeEach, vi } from 'vitest';
import '../../resources/js/app.js';

describe('Theme application', () => {

    beforeEach(() => {
        // Reset the body for each test
        document.body.removeAttribute('data-bs-theme');
        document.body.removeAttribute('data-theme-mode');
        document.body.classList.remove('theme-dark', 'dark-mode', 'light-mode');
        
        // Mock console.log to prevent logs from appearing in test output
        vi.spyOn(console, 'log').mockImplementation(() => {});
    });

    it('should apply dark theme correctly', () => {
        // Act
        window.applyTheme('dark');

        // Assert
        expect(document.body.getAttribute('data-bs-theme')).toBe('dark');
        expect(document.body.getAttribute('data-theme-mode')).toBe('dark');
        expect(document.body.classList.contains('theme-dark')).toBe(true);
        expect(document.body.classList.contains('dark-mode')).toBe(true);
        expect(document.body.classList.contains('light-mode')).toBe(false);
    });

    it('should apply light theme correctly', () => {
        // Arrange: start with dark theme
        window.applyTheme('dark');

        // Act
        window.applyTheme('light');

        // Assert
        expect(document.body.getAttribute('data-bs-theme')).toBe('light');
        expect(document.body.getAttribute('data-theme-mode')).toBe('light');
        expect(document.body.classList.contains('theme-dark')).toBe(false);
        expect(document.body.classList.contains('dark-mode')).toBe(false);
        expect(document.body.classList.contains('light-mode')).toBe(true);
    });

    it('should remove dark classes when switching to light theme', () => {
        // Arrange
        document.body.classList.add('theme-dark', 'dark-mode');
        document.body.setAttribute('data-bs-theme', 'dark');

        // Act
        window.applyTheme('light');

        // Assert
        expect(document.body.classList.contains('theme-dark')).toBe(false);
        expect(document.body.classList.contains('dark-mode')).toBe(false);
        expect(document.body.classList.contains('light-mode')).toBe(true);
        expect(document.body.getAttribute('data-bs-theme')).toBe('light');
    });
});
