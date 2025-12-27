import { describe, it, expect, vi } from 'vitest';
import * as BulkSelection from '@/js/admin/partials/sales-order/bulkActions/SalesOrderBulkSelection.js';

describe('Sales Order - Bulk Selection', () => {
    it('should load and be defined', () => {
        expect(BulkSelection).toBeDefined();
    });
});
