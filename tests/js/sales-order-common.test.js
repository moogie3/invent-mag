import { describe, it, expect, vi } from 'vitest';
import * as SalesOrderModule from '@/js/admin/partials/sales-order/common/SalesOrderModule.js';

describe('Sales Order - Common Module', () => {
    it('should load and be defined', () => {
        expect(SalesOrderModule).toBeDefined();
    });
});
