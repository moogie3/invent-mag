import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/pos.js';
describe('resources/js/admin/pos.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
