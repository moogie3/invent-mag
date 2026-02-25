import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/layouts/theme-initializer.js';
describe('resources/js/admin/layouts/theme-initializer.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
