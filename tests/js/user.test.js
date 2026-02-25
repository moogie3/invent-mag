import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/user.js';
describe('resources/js/admin/user.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
