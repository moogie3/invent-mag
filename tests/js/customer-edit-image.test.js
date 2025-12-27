import { describe, it, expect } from 'vitest';
import * as EditImage from '@/js/admin/partials/customer/editModal/image.js';

describe('Customer - Edit Modal Image', () => {
    it('should load and be defined', () => {
        expect(EditImage).toBeDefined();
    });
});
