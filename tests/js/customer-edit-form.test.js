import { describe, it, expect } from 'vitest';
import * as EditForm from '@/js/admin/partials/customer/editModal/form.js';

describe('Customer - Edit Modal Form', () => {
    it('should load and be defined', () => {
        expect(EditForm).toBeDefined();
    });
});
