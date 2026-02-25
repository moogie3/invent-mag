import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/helpers/notification.js';
describe('resources/js/admin/helpers/notification.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
