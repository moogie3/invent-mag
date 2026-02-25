import { describe, it, expect, vi } from 'vitest';
import * as BulkActions from '@/js/admin/partials/purchase-order/bulkActions/actions.js';

describe('Purchase Order - Bulk Actions', () => {
    it('should load and be defined', () => {
        expect(BulkActions).toBeDefined();
    });
});
