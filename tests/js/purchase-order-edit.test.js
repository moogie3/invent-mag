import { describe, it, expect, vi } from 'vitest';
import * as Edit from '@/js/admin/partials/purchase-order/edit/PurchaseOrderEdit.js';

describe('Purchase Order - Edit', () => {
    it('should load and be defined', () => {
        expect(Edit).toBeDefined();
    });
});
