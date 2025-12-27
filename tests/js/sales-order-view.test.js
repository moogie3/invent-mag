import { describe, it, expect, vi } from 'vitest';
import * as View from '@/js/admin/partials/sales-order/view/SalesOrderView.js';

describe('Sales Order - View', () => {
    it('should load and be defined', () => {
        expect(View).toBeDefined();
    });
});
