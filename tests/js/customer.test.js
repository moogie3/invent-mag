import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/customer.js';
describe('resources/js/admin/customer.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
