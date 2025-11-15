import { describe, it, expect } from 'vitest';
import * as CrmApi from '@/js/admin/partials/customer/crmModal/api.js';

describe('Customer - CRM Modal API', () => {
    it('should load and be defined', () => {
        expect(CrmApi).toBeDefined();
    });
});
