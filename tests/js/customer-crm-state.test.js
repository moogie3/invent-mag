import { describe, it, expect } from 'vitest';
import * as CrmState from '@/js/admin/partials/customer/crmModal/state.js';

describe('Customer - CRM Modal State', () => {
    it('should load and be defined', () => {
        expect(CrmState).toBeDefined();
    });
});
