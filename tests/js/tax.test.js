import { describe, it, expect, vi } from 'vitest';

// tax.js registers keyboard shortcuts via window.shortcutManager on import,
// so provide a stub before the module loads.
vi.hoisted(() => {
    window.shortcutManager = {
        register: () => {},
        unregister: () => {},
    };
});

import * as Module from '@/js/admin/tax.js';
describe('resources/js/admin/tax.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
