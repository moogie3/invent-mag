import { getProducts, setProducts, saveProductsToCache } from './state.js';
import { renderList } from './dom.js';
import { playSuccessSound, playDeleteSound, playDecreaseSound } from '../utils/sound.js';
import { showAddToCartFeedback, updateProductCardStockDisplay } from '../utils/ui.js';

const productList = document.getElementById("productList");
const productGrid = document.getElementById("productGrid");
const clearCartBtn = document.getElementById("clearCart");

function updateProductPrice(index, newPrice) {
    const products = getProducts();
    if (isNaN(index) || index < 0 || index >= products.length) return;

    products[index].price = parseFloat(newPrice);
    products[index].total =
        products[index].quantity * products[index].price;

    setProducts(products);
    renderList();
}

function addToProductList(
    productId,
    productName,
    productPrice,
    productUnit,
    productStock
) {
    let products = getProducts();
    let existingProduct = products.find((p) => p.id === productId);

    if (existingProduct) {
        if (existingProduct.quantity + 1 > existingProduct.stock) {
            window.showToast("Warning", "Insufficient Stock", "warning");
            return;
        }
        existingProduct.quantity += 1;
        existingProduct.total =
            existingProduct.quantity * existingProduct.price;
    } else {
        if (1 > productStock) {
            window.showToast("Warning", "Insufficient Stock", "warning");
            return;
        }
        products.push({
            id: productId,
            name: productName,
            price: parseFloat(productPrice),
            quantity: 1,
            total: parseFloat(productPrice),
            unit: productUnit,
            stock: productStock,
        });
    }

    setProducts(products);
    updateProductCardStockDisplay(productId, products.find(p => p.id === productId).stock - products.find(p => p.id === productId).quantity);
    showAddToCartFeedback();
    playSuccessSound();
    renderList();
    saveProductsToCache();
}

function clearCart() {
    const products = getProducts();
    products.forEach(product => {
        updateProductCardStockDisplay(product.id, product.stock);
    });
    setProducts([]);
    renderList();
    saveProductsToCache();
}

export function initCartActions() {
    productGrid.addEventListener("click", function (event) {
        const productCard = event.target.closest(".product-card");
        if (!productCard) return;

        const productId = productCard.dataset.productId;
        const productName = productCard.dataset.productName;
        const productPrice = productCard.dataset.productPrice;
        const productUnit = productCard.dataset.productUnit;
        const productStock = parseInt(productCard.dataset.productStock);

        addToProductList(productId, productName, productPrice, productUnit, productStock);
    });

    clearCartBtn.addEventListener("click", function () {
        const products = getProducts();
        if (products.length === 0) return;
        playDeleteSound();
        clearCart();
    });

    productList.addEventListener("click", function (event) {
        const removeBtn = event.target.closest(".remove-product");
        const increaseBtn = event.target.closest(".increase-product");
        const decreaseBtn = event.target.closest(".decrease-product");
        let products = getProducts();

        if (removeBtn) {
            event.preventDefault();
            const index = parseInt(removeBtn.dataset.index);
            if (!isNaN(index)) {
                const removedProduct = products[index];
                playDeleteSound();
                products.splice(index, 1);
                updateProductCardStockDisplay(removedProduct.id, removedProduct.stock);
                setProducts(products);
                renderList();
            }
        } else if (decreaseBtn) {
            event.preventDefault();
            const index = parseInt(decreaseBtn.dataset.index);
            if (!isNaN(index)) {
                const product = products[index];
                if (product.quantity > 1) {
                    playDecreaseSound();
                    product.quantity -= 1;
                    product.total = product.quantity * product.price;
                    updateProductCardStockDisplay(product.id, product.stock - product.quantity);
                } else {
                    playDeleteSound();
                    products.splice(index, 1);
                    updateProductCardStockDisplay(product.id, product.stock);
                }
                setProducts(products);
                renderList();
            }
        } else if (increaseBtn) {
            event.preventDefault();
            const index = parseInt(increaseBtn.dataset.index);
            if (!isNaN(index)) {
                const product = products[index];
                if (product.quantity + 1 > product.stock) {
                    window.showToast("Warning", "Insufficient Stock", "warning");
                    return;
                }
                playSuccessSound();
                product.quantity += 1;
                product.total = product.quantity * product.price;
                updateProductCardStockDisplay(product.id, product.stock - product.quantity);
                setProducts(products);
                renderList();
            }
        }
    });

    productList.addEventListener("change", function (event) {
        const quantityInput = event.target.closest(".quantity-input");
        const priceInput = event.target.closest(".price-input");
        let products = getProducts();

        if (quantityInput) {
            const index = parseInt(quantityInput.dataset.index);
            const newQuantity = parseInt(quantityInput.value);

            if (!isNaN(index) && !isNaN(newQuantity) && newQuantity >= 0) {
                const product = products[index];

                if (newQuantity > product.stock) {
                    window.showToast("Warning", "Insufficient Stock", "warning");
                    quantityInput.value = product.quantity;
                    return;
                }

                if (newQuantity === 0) {
                    playDeleteSound();
                    products.splice(index, 1);
                    updateProductCardStockDisplay(product.id, product.stock);
                } else {
                    product.quantity = newQuantity;
                    product.total = product.quantity * product.price;
                    updateProductCardStockDisplay(product.id, product.stock - product.quantity);
                }
                setProducts(products);
                renderList();
            }
        }

        if (priceInput) {
            const index = parseInt(priceInput.dataset.index);
            const newPrice = parseFloat(priceInput.value);

            if (!isNaN(index) && !isNaN(newPrice) && newPrice >= 0) {
                updateProductPrice(index, newPrice);
            }
        }
    });

    productList.addEventListener("keypress", function (event) {
        if (event.target.closest(".price-input") && event.key === "Enter") {
            event.preventDefault();
            event.target.blur();
        }
        if (event.target.closest(".quantity-input") && event.key === "Enter") {
            event.preventDefault();
            event.target.blur();
        }
    });
}
