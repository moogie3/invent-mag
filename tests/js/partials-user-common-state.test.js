import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/partials/user/common/state.js';
describe('resources/js/admin/partials/user/common/state.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
