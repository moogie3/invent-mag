import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/partials/sales-pipeline/data/init.js';
describe('resources/js/admin/partials/sales-pipeline/data/init.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
