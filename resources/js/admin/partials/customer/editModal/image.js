export function handleCustomerImage(button) {
    const customerId = button.getAttribute("data-id") || "";
    const customerName = button.getAttribute("data-name") || "";
    const customerImage = button.getAttribute("data-image") || "";

    const currentCustomerImageContainer = document.getElementById(
        "currentCustomerImageContainer"
    );
    const defaultPlaceholderUrl =
        window.defaultPlaceholderUrl || "/img/default_placeholder.png";

    if (currentCustomerImageContainer) {
        if (
            customerImage &&
            customerImage !== "" &&
            customerImage !== defaultPlaceholderUrl
        ) {
            currentCustomerImageContainer.innerHTML = `
                <img src="${customerImage}" alt="${
                customerName || "Customer Image"
            }"
                     class="img-thumbnail"
                     style="max-width: 80px; max-height: 80px; object-fit: cover;">
            `;
        } else {
            currentCustomerImageContainer.innerHTML = `
                <div class="img-thumbnail d-flex align-items-center justify-content-center"
                     style="width: 80px; height: 80px; margin: 0 auto;">
                    <i class="ti ti-photo fs-1 text-muted"></i>
                </div>
            `;
        }
    }

    if (!customerImage || customerImage === "") {
        fetch(`/admin/customers/${customerId}/details`)
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
                    data.customer &&
                    currentCustomerImageContainer
                ) {
                    if (
                        data.customer.image &&
                        data.customer.image !== defaultPlaceholderUrl
                    ) {
                        currentCustomerImageContainer.innerHTML = `
                            <img src="${data.customer.image}" alt="${
                            data.customer.name || "Customer Image"
                        }"
                                 class="img-thumbnail"
                                 style="max-width: 80px; max-height: 80px; object-fit: cover;">
                        `;
                    } else {
                        currentCustomerImageContainer.innerHTML = `
                            <div class="img-thumbnail d-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px; margin: 0 auto;">
                                <i class="ti ti-photo fs-1 text-muted"></i>
                            </div>
                        `;
                    }
                }
            })
            .catch((error) => {
                // // console.error(
                //     "Error fetching customer details for edit modal:",
                //     error
                // );
                if (currentCustomerImageContainer) {
                    currentCustomerImageContainer.innerHTML = `
                        <div class="img-thumbnail d-flex align-items-center justify-content-center"
                             style="width: 80px; height: 80px; margin: 0 auto;">
                            <i class="ti ti-photo fs-1 text-muted"></i>
                        </div>
                    `;
                }
            });
    }
}
