import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/unit.js';
describe('resources/js/admin/unit.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
