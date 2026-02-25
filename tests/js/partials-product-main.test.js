import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/partials/product/main.js';
describe('resources/js/admin/partials/product/main.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
