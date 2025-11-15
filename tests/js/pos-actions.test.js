import { describe, it, expect, beforeEach, vi } from 'vitest';

// Mock all dependencies
vi.mock('@/js/admin/partials/pos/cart/state.js', () => ({
    getProducts: vi.fn(),
    setProducts: vi.fn(),
    saveProductsToCache: vi.fn(),
}));

vi.mock('@/js/admin/partials/pos/cart/dom.js', () => ({
    renderList: vi.fn(),
}));

vi.mock('@/js/admin/partials/pos/utils/sound.js', () => ({
    playSuccessPosSound: vi.fn(),
    playDeleteSound: vi.fn(),
    playDecreaseSound: vi.fn(),
}));

vi.mock('@/js/admin/partials/pos/utils/ui.js', () => ({
    showAddToCartFeedback: vi.fn(),
    updateProductCardStockDisplay: vi.fn(),
}));

// Mock the global toast notification object
global.InventMagApp = {
    showToast: vi.fn(),
};

import {
    addToProductList,
    removeProduct,
    decreaseQuantity,
    increaseQuantity,
    updateQuantity,
    clearCart
} from '@/js/admin/partials/pos/cart/actions.js';
import { getProducts, setProducts } from '@/js/admin/partials/pos/cart/state.js';

describe('POS Cart Actions', () => {
    let mockProducts;

    beforeEach(() => {
        // Reset mocks before each test
        vi.clearAllMocks();
        
        // Clone this base state for each test to ensure isolation
        mockProducts = [
            { id: 1, name: 'Product A', price: 10, quantity: 2, total: 20, stock: 10 },
            { id: 2, name: 'Product B', price: 20, quantity: 1, total: 20, stock: 5 },
        ];
        
        // getProducts will return a mutable copy of our mockProducts
        getProducts.mockImplementation(() => [...mockProducts]);
    });

    describe('addToProductList', () => {
        it('should add a new product to the list', () => {
            addToProductList(3, 'Product C', 30, 'pcs', 10);
            
            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts.length).toBe(3);
            expect(newProducts[2].name).toBe('Product C');
            expect(newProducts[2].quantity).toBe(1);
        });

        it('should increase the quantity of an existing product', () => {
            addToProductList(1, 'Product A', 10, 'pcs', 10);

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts.length).toBe(2);
            expect(newProducts[0].quantity).toBe(3);
        });

        it('should not add a product if stock is insufficient', () => {
            addToProductList(3, 'Product C', 30, 'pcs', 0);
            
            expect(setProducts).not.toHaveBeenCalled();
            expect(global.InventMagApp.showToast).toHaveBeenCalledWith("Warning", "Insufficient Stock", "warning");
        });

        it('should not increase quantity if stock is insufficient', () => {
            const productA = mockProducts.find(p => p.id === 1);
            productA.quantity = 10; // Set quantity to max stock

            addToProductList(1, 'Product A', 10, 'pcs', 10);

            expect(setProducts).not.toHaveBeenCalled();
            expect(global.InventMagApp.showToast).toHaveBeenCalledWith("Warning", "Insufficient Stock", "warning");
        });
    });

    describe('removeProduct', () => {
        it('should remove a product from the list by index', () => {
            removeProduct(0); // Remove Product A

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts.length).toBe(1);
            expect(newProducts[0].id).toBe(2); // Product B should remain
        });
    });

    describe('decreaseQuantity', () => {
        it('should decrease a product quantity by 1 if quantity > 1', () => {
            decreaseQuantity(0); // Decrease Product A quantity from 2 to 1

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts[0].quantity).toBe(1);
        });

        it('should remove the product if quantity is 1', () => {
            decreaseQuantity(1); // Decrease Product B quantity from 1 to 0

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts.length).toBe(1);
            expect(newProducts[0].id).toBe(1); // Product A should remain
        });
    });

    describe('increaseQuantity', () => {
        it('should increase a product quantity by 1', () => {
            increaseQuantity(1); // Increase Product B quantity from 1 to 2

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts[1].quantity).toBe(2);
        });

        it('should not increase quantity if stock is insufficient', () => {
            const productB = mockProducts.find(p => p.id === 2);
            productB.quantity = 5; // Set quantity to max stock

            increaseQuantity(1);

            expect(setProducts).not.toHaveBeenCalled();
            expect(global.InventMagApp.showToast).toHaveBeenCalledWith("Warning", "Insufficient Stock", "warning");
        });
    });

    describe('updateQuantity', () => {
        it('should update a product to a specific quantity', () => {
            updateQuantity(0, 5); // Update Product A quantity to 5

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts[0].quantity).toBe(5);
        });

        it('should remove the product if new quantity is 0', () => {
            updateQuantity(0, 0);

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts.length).toBe(1);
            expect(newProducts[0].id).toBe(2);
        });

        it('should not update quantity if new quantity is greater than stock', () => {
            updateQuantity(0, 11); // Product A stock is 10

            expect(setProducts).not.toHaveBeenCalled();
            expect(global.InventMagApp.showToast).toHaveBeenCalledWith("Warning", "Insufficient Stock", "warning");
        });
    });

    describe('clearCart', () => {
        it('should remove all products from the cart', () => {
            clearCart();

            const newProducts = setProducts.mock.calls[0][0];
            expect(setProducts).toHaveBeenCalledTimes(1);
            expect(newProducts.length).toBe(0);
        });
    });
});
