import { allCustomers } from '../common/state.js';

export function populateCustomerSelect(selectElement) {
    if (!selectElement) return;

    selectElement.innerHTML = '<option value="">Select Customer</option>';
    allCustomers.forEach((customer) => {
        const option = document.createElement("option");
        option.value = customer.id;
        option.textContent = customer.name;
        selectElement.appendChild(option);
    });
}
