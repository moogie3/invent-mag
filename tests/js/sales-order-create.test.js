import { describe, it, expect, vi } from 'vitest';
import * as Create from '@/js/admin/partials/sales-order/create/SalesOrderCreate.js';

describe('Sales Order - Create', () => {
    it('should load and be defined', () => {
        expect(Create).toBeDefined();
    });
});
