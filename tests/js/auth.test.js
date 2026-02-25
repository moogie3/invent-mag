import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/auth.js';
describe('resources/js/admin/auth.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
