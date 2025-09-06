import { SALES_PIPELINE_ROUTES } from '../common/constants.js';
import { fetchData } from '../data/init.js';
import { loadPipelineBoard } from '../ui/pipelineBoard.js';
import { createProductItemRow, calculateTotalAmount } from '../modals/opportunityItems.js';

const editOpportunityForm = document.getElementById("editOpportunityForm");
const editOpportunityItemsContainer = document.getElementById(
    "editOpportunityItemsContainer"
);
const pipelineSelect = document.getElementById("pipelineSelect");

let editOpportunityExpectedCloseDateFlatpickr;

export function initEditOpportunityForm() {
    editOpportunityForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const opportunityId =
            document.getElementById("editOpportunityId").value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        data.sales_pipeline_id = document.getElementById(
            "editOpportunityPipelineId"
        ).value;
        data.pipeline_stage_id = document.getElementById(
            "editOpportunityStageId"
        ).value;

        const items = [];
        editOpportunityItemsContainer
            .querySelectorAll(".product-item-row")
            .forEach((row, index) => {
                items.push({
                    product_id: row.querySelector(".product-select").value,
                    quantity: row.querySelector(".quantity-input").value,
                    price: row.querySelector(".price-input").value,
                });
            });
        data.items = items;

        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": CSRF_TOKEN,
                    },
                    body: JSON.stringify(data),
                }
            );

            if (!response.ok) {
                throw new Error("Failed to update opportunity");
            }

            const currentPipelineId = pipelineSelect.value;
            bootstrap.Modal.getInstance(
                document.getElementById("editOpportunityModal")
            ).hide();
            await fetchData();
            pipelineSelect.value = currentPipelineId;
            loadPipelineBoard(currentPipelineId);

            window.showToast(
                "Success",
                "Opportunity updated successfully!",
                "success"
            );
        } catch (error) {
            console.error("Error updating opportunity:", error);
            window.showToast(
                "Error",
                "Failed to update opportunity. Please try again.",
                "error"
            );
        }
    });
}

export async function loadEditOpportunityModal(opportunityId) {
    try {
        const response = await fetch(
            `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`
        );
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            console.error(
                `Server error fetching opportunity: Status ${response.status} - ${response.statusText}`,
                errorData
            );
            throw new Error("Failed to fetch opportunity");
        }
        const opportunity = await response.json();

        document.getElementById("editOpportunityId").value =
            opportunity.id;
        document.getElementById("editOpportunityName").value =
            opportunity.name;
        document.getElementById("editOpportunityPipelineId").value =
            opportunity.sales_pipeline_id;
        document.getElementById("editOpportunityStageId").value =
            opportunity.pipeline_stage_id;
        document.getElementById("editOpportunityPipelineId").value =
            opportunity.sales_pipeline_id;
        document.getElementById("editOpportunityStageId").value =
            opportunity.pipeline_stage_id;
        document.getElementById("editOpportunityCustomer").value =
            opportunity.customer_id;
        document.getElementById("editOpportunityStatus").value =
            opportunity.status;

        if (!editOpportunityExpectedCloseDateFlatpickr) {
            editOpportunityExpectedCloseDateFlatpickr = flatpickr(
                document.getElementById(
                    "editOpportunityExpectedCloseDate"
                ),
                {
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d F Y",
                    allowInput: true,
                }
            );
        }

        if (opportunity.expected_close_date) {
            editOpportunityExpectedCloseDateFlatpickr.setDate(
                opportunity.expected_close_date
            );
        } else {
            editOpportunityExpectedCloseDateFlatpickr.clear();
        }

        editOpportunityItemsContainer.innerHTML = "";
        if (opportunity.items && Array.isArray(opportunity.items)) {
            opportunity.items.forEach((item, index) => {
                editOpportunityItemsContainer.appendChild(
                    createProductItemRow(
                        item,
                        index,
                        "editOpportunityItemsContainer"
                    )
                );
            });
        }
        calculateTotalAmount("editOpportunityItemsContainer");

        new bootstrap.Modal(
            document.getElementById("editOpportunityModal")
        ).show();
    } catch (error) {
        console.error("Error in edit opportunity fetch:", error);
        window.showToast(
            "Error",
            "Failed to fetch opportunity details. Please try again.",
            "error"
        );
    }
}
