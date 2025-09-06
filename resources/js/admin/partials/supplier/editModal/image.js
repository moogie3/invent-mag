export function handleSupplierImage(button) {
    const supplierId = button.getAttribute("data-id") || "";
    const supplierName = button.getAttribute("data-name") || "";
    const supplierImage = button.getAttribute("data-image") || "";

    const currentSupplierImageContainer = document.getElementById(
        "currentSupplierImageContainer"
    );
    const defaultPlaceholderUrl =
        window.defaultPlaceholderUrl || "/img/default_placeholder.png";

    if (currentSupplierImageContainer) {
        if (
            supplierImage &&
            supplierImage !== "" &&
            supplierImage !== defaultPlaceholderUrl
        ) {
            currentSupplierImageContainer.innerHTML = `
                <img src="${supplierImage}" alt="${supplierName || 'Supplier Image'}"
                             class="img-thumbnail"
                             style="max-width: 80px; max-height: 80px; object-fit: cover;">
            `;
        } else {
            currentSupplierImageContainer.innerHTML = `
                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="ti ti-photo fs-1 text-muted"></i>
                        </div>
            `;
        }
    }

    if (!supplierImage || supplierImage === "") {
        fetch(`/admin/suppliers/${supplierId}/srm-details`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        `HTTP error! status: ${response.status}`
                    );
                }
                return response.json();
            })
            .then((data) => {
                if (
                    data &&
                    data.supplier &&
                    currentSupplierImageContainer
                ) {
                    if (
                        data.supplier.image &&
                        data.supplier.image !== defaultPlaceholderUrl
                    ) {
                        currentSupplierImageContainer.innerHTML = `
                            <img src="${data.supplier.image}" alt="${data.supplier.name || 'Supplier Image'}"
                                         class="img-thumbnail"
                                         style="max-width: 80px; max-height: 80px; object-fit: cover;">
                        `;
                    } else {
                        currentSupplierImageContainer.innerHTML = `
                            <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                         style="width: 80px; height: 80px; margin: 0 auto;">
                                    <i class="ti ti-photo fs-1 text-muted"></i>
                                </div>
                        `;
                    }
                }
            })
            .catch((error) => {
                console.error(
                    "Error fetching supplier details for edit modal:",
                    error
                );
                if (currentSupplierImageContainer) {
                    currentSupplierImageContainer.innerHTML = `
                        <div class="img-thumbnail d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="ti ti-photo fs-1 text-muted"></i>
                        </div>
                    `;
                }
            });
    }
}
