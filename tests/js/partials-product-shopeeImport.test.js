import { describe, it, expect, beforeEach } from 'vitest';
import { initShopeeImport } from '@/js/admin/partials/product/shopeeImport.js';

describe('resources/js/admin/partials/product/shopeeImport.js', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <meta name="csrf-token" content="test-token">
            <form id="shopeeImportForm" action="/admin/product/shopee-import" method="POST"
                data-label-updated="Updated"
                data-label-created="Created"
                data-label-skipped="Skipped"
                data-label-errors="Errors"
                data-label-images="Images"
                data-label-split-families="Split">
            </form>
            <div id="shopeeImportResult" style="display: none;"></div>
            <button type="submit" form="shopeeImportForm" id="shopeeImportSubmitBtn"></button>
        `;
    });

    it('loads and exports initShopeeImport', () => {
        expect(initShopeeImport).toBeTypeOf('function');
    });

    it('does nothing when the form is missing', () => {
        document.body.innerHTML = '';
        expect(() => initShopeeImport()).not.toThrow();
    });

    it('attaches the submit handler only once', () => {
        initShopeeImport();
        initShopeeImport();
        const form = document.getElementById('shopeeImportForm');
        expect(form.dataset.handlerAttached).toBe('true');
    });
});
