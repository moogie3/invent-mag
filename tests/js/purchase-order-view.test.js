import { describe, it, expect, vi } from 'vitest';
import * as View from '@/js/admin/partials/purchase-order/view/PurchaseOrderView.js';

describe('Purchase Order - View', () => {
    it('should load and be defined', () => {
        expect(View).toBeDefined();
    });
});
