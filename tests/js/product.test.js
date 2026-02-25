import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/product.js';
describe('resources/js/admin/product.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
