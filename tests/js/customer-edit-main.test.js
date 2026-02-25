import { describe, it, expect } from 'vitest';
import * as EditMain from '@/js/admin/partials/customer/editModal/main.js';

describe('Customer - Edit Modal Main', () => {
    it('should load and be defined', () => {
        expect(EditMain).toBeDefined();
    });
});
