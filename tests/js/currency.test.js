import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/currency.js';
describe('resources/js/admin/currency.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
