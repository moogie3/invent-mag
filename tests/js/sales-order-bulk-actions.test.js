import { describe, it, expect, vi } from 'vitest';
import * as BulkActions from '@/js/admin/partials/sales-order/bulkActions/actions.js';

describe('Sales Order - Bulk Actions', () => {
    it('should load and be defined', () => {
        expect(BulkActions).toBeDefined();
    });
});
