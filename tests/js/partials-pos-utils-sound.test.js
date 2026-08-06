import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/utils/sound.js';
describe('resources/js/admin/utils/sound.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
