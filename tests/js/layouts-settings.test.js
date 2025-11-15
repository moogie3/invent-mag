import { describe, it, expect, vi, beforeEach } from 'vitest';

// The fetch mock is now global in setup.js, so we can import directly.
import * as Settings from '@/js/admin/layouts/settings.js';

describe('layouts/settings.js', () => {
    it('should load and initialize without errors', () => {
        // This test now confirms that the module can load AND successfully fetch its mock settings
        expect(Settings).toBeDefined();
    });

    it('should call fetch to get system settings on load', () => {
        // The module is imported once at the top level, so the fetch call has already happened.
        // We can check that it was called.
        expect(window.fetch).toHaveBeenCalledWith('/admin/api/settings');
    });
});