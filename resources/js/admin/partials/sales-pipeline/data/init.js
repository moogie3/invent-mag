import { SALES_PIPELINE_ROUTES } from '../common/constants.js';
import { allPipelines, allCustomers, allProducts, setAllPipelines, setAllCustomers, setAllProducts, setCurrencySettings } from '../common/state.js';
import { renderPipelinesSelect } from '../ui/pipelineSelect.js';
import { renderPipelinesList } from '../ui/pipelinesList.js';
import { populateCustomerSelect } from '../ui/customerSelect.js';

export async function initializeData() {
    const initialDataContainer = document.querySelector(
        ".card-body[data-initial-pipelines]"
    );

    if (initialDataContainer) {
        const pipelinesData = initialDataContainer.dataset.initialPipelines;
        const customersData = initialDataContainer.dataset.initialCustomers;

        setCurrencySettings({
            symbol: initialDataContainer.dataset.currencySymbol || "$",
            decimalPlaces: parseInt(
                initialDataContainer.dataset.decimalPlaces || 2
            ),
            decimalSeparator: initialDataContainer.dataset.decimalSeparator || ".",
            thousandSeparator: initialDataContainer.dataset.thousandSeparator || ",",
            currency_code: initialDataContainer.dataset.currencyCode || "USD",
        });

        try {
            if (pipelinesData && pipelinesData.trim()) {
                setAllPipelines(JSON.parse(pipelinesData));
            }

            if (customersData && customersData.trim()) {
                setAllCustomers(JSON.parse(customersData));
            }
        } catch (error) {
            console.error("Error parsing initial data:", error);
            window.showToast(
                "Error",
                "Error loading initial data. Please refresh the page.",
                "error"
            );
            return;
        }
    }

    if (allPipelines.length > 0) {
        renderPipelinesSelect();
    } else {
        console.warn("No pipelines found");
    }

    renderPipelinesList();

    const opportunityCustomerSelect = document.getElementById(
        "opportunityCustomer"
    );
    const editOpportunityCustomerSelect = document.getElementById(
        "editOpportunityCustomer"
    );

    if (allCustomers.length > 0) {
        populateCustomerSelect(opportunityCustomerSelect);
        populateCustomerSelect(editOpportunityCustomerSelect);
    } else {
        console.warn("No customers found");
    }

    try {
        const productsResponse = await fetch(
            SALES_PIPELINE_ROUTES.productIndex
        );
        if (!productsResponse.ok) {
            throw new Error("Failed to fetch products");
        }
        setAllProducts(await productsResponse.json());
    } catch (error) {
        console.error("Error fetching products:", error);
        window.showToast(
            "Error",
            "Failed to load product data. Please refresh the page.",
            "error"
        );
    }
}

export async function fetchData() {
    try {
        const pipelinesResponse = await fetch(
            SALES_PIPELINE_ROUTES.pipelinesIndex
        );
        if (!pipelinesResponse.ok) {
            throw new Error("Failed to fetch pipelines");
        }
        setAllPipelines(await pipelinesResponse.json());

        renderPipelinesSelect(false);
        renderPipelinesList();
    } catch (error) {
        console.error("Error re-fetching data:", error);
        window.showToast(
            "Error",
            "Failed to re-fetch data. Please try again.",
            "error"
        );
    }
}
