import { describe, it, expect } from 'vitest';
import * as CrmMain from '@/js/admin/partials/customer/crmModal/main.js';

describe('Customer - CRM Modal Main', () => {
    it('should load and be defined', () => {
        expect(CrmMain).toBeDefined();
    });
});
