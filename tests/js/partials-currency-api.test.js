import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/partials/currency/api.js';
describe('resources/js/admin/partials/currency/api.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
