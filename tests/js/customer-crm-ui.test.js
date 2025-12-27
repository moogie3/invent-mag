import { describe, it, expect } from 'vitest';
import * as CrmUi from '@/js/admin/partials/customer/crmModal/ui.js';

describe('Customer - CRM Modal UI', () => {
    it('should load and be defined', () => {
        expect(CrmUi).toBeDefined();
    });
});
