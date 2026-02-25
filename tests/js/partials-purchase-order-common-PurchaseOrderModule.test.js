import { describe, it, expect } from 'vitest';
import * as Module from '@/js/admin/partials/purchase-order/common/PurchaseOrderModule.js';
describe('resources/js/admin/partials/purchase-order/common/PurchaseOrderModule.js', () => {
    it('should load and be defined', () => {
        expect(Module).toBeDefined();
    });
});
