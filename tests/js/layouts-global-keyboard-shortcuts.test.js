import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/layouts/global-keyboard-shortcuts.js';
describe('resources/js/admin/layouts/global-keyboard-shortcuts.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
