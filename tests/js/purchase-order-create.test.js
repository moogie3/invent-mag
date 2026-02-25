import { describe, it, expect, vi } from 'vitest';
import * as Create from '@/js/admin/partials/purchase-order/create/PurchaseOrderCreate.js';

describe('Purchase Order - Create', () => {
    it('should load and be defined', () => {
        expect(Create).toBeDefined();
    });
});
