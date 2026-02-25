import { describe, it, expect, vi } from 'vitest';
import * as BulkSelection from '@/js/admin/partials/purchase-order/bulkActions/PurchaseOrderBulkSelection.js';

describe('Purchase Order - Bulk Selection', () => {
    it('should load and be defined', () => {
        expect(BulkSelection).toBeDefined();
    });
});
