import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/layouts/page-loader.js';
describe('resources/js/admin/layouts/page-loader.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
