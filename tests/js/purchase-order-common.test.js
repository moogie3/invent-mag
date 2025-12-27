import { describe, it, expect, vi } from 'vitest';
import * as PurchaseOrderModule from '@/js/admin/partials/purchase-order/common/PurchaseOrderModule.js';

describe('Purchase Order - Common Module', () => {
    it('should load and be defined', () => {
        expect(PurchaseOrderModule).toBeDefined();
    });
});
