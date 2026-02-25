import { describe, it, expect, beforeEach, vi } from 'vitest';

// Since this module interacts with localStorage, we need to ensure it's clean for each test.
// JSDOM provides a localStorage implementation, so we just need to manage it.

import {
    getProducts,
    setProducts,
    saveProductsToCache,
    loadProductsFromCache
} from '@/js/admin/partials/pos/cart/state.js';

describe('POS Cart State Management', () => {

    beforeEach(() => {
        // Clear products and localStorage before each test to ensure isolation
        setProducts([]);
        localStorage.clear();
    });

    it('should set and get the products array', () => {
        const newProducts = [{ id: 1, name: 'Test' }];
        
        // Act
        setProducts(newProducts);
        const retrievedProducts = getProducts();

        // Assert
        expect(retrievedProducts).toEqual(newProducts);
        // Ensure it's not the same array instance if the implementation were to change
        expect(retrievedProducts).not.toBe(newProducts); 
    });

    it('should save the current products array to localStorage', () => {
        const productsToSave = [{ id: 1, name: 'Product A' }];
        setProducts(productsToSave);

        // Act
        saveProductsToCache();

        // Assert
        const cachedData = localStorage.getItem('cachedProducts');
        expect(cachedData).toBe(JSON.stringify(productsToSave));
    });

    describe('loadProductsFromCache', () => {
        it('should load products from localStorage if data exists', () => {
            const cachedProducts = [{ id: 2, name: 'Product B' }];
            localStorage.setItem('cachedProducts', JSON.stringify(cachedProducts));

            // Act
            loadProductsFromCache();

            // Assert
            const loadedProducts = getProducts();
            expect(loadedProducts).toEqual(cachedProducts);
        });

        it('should do nothing if no data exists in localStorage', () => {
            // Arrange: ensure state is empty and localStorage is clear (done in beforeEach)
            
            // Act
            loadProductsFromCache();

            // Assert
            const products = getProducts();
            expect(products).toEqual([]);
        });

        it('should handle invalid JSON in localStorage gracefully', () => {
            // Arrange
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            localStorage.setItem('cachedProducts', 'this is not valid json');
            
            // Act & Assert
            // The function should not throw an error, and the products array should remain empty.
            expect(() => loadProductsFromCache()).not.toThrow();
            const products = getProducts();
            expect(products).toEqual([]);

            // Clean up the spy
            consoleSpy.mockRestore();
        });
    });
});
