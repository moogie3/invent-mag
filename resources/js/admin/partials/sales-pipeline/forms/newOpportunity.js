import { SALES_PIPELINE_ROUTES, CSRF_TOKEN } from "../common/constants.js";
import { allPipelines } from "../common/state.js";
import { formatCurrency } from "../common/utils.js";
import { loadPipelineBoard } from "../ui/pipelineBoard.js";

const newOpportunityForm = document.getElementById("newOpportunityForm");
const newOpportunityPipelineId = document.getElementById(
    "newOpportunityPipelineId"
);
const newOpportunityStageId = document.getElementById("newOpportunityStageId");
const newOpportunityItemsContainer = document.getElementById(
    "newOpportunityItemsContainer"
);
const newOpportunityTotalAmountInput = document.getElementById(
    "newOpportunityTotalAmount"
);
const pipelineSelect = document.getElementById("pipelineSelect");

export function initNewOpportunityForm() {
    newOpportunityForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const selectedPipelineId = pipelineSelect.value;

        const selectedPipeline = allPipelines.find((p) => {
            return String(p.id) === String(selectedPipelineId);
        });

        if (!selectedPipeline) {
            InventMagApp.showToast(
                "Error",
                "Please select a pipeline first.",
                "error"
            );
            return;
        }

        if (!selectedPipeline.stages || selectedPipeline.stages.length === 0) {
            InventMagApp.showToast(
                "Error",
                "The selected pipeline has no stages. Please add stages to the pipeline first.",
                "error"
            );
            return;
        }

        newOpportunityPipelineId.value = selectedPipelineId;
        const firstStage = selectedPipeline.stages.sort(
            (a, b) => a.position - b.position
        )[0];
        newOpportunityStageId.value = firstStage.id;

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        const items = [];
        newOpportunityItemsContainer
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
                SALES_PIPELINE_ROUTES.opportunitiesStore,
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
                const errorData = await response.json().catch(() => ({}));
                console.error("Server response:", errorData);
                throw new Error(
                    `Failed to create opportunity: ${response.status}`
                );
            }

            const opportunity = await response.json();

            bootstrap.Modal.getInstance(
                document.getElementById("newOpportunityModal")
            ).hide();
            this.reset();
            newOpportunityItemsContainer.innerHTML = "";
            newOpportunityTotalAmountInput.value = formatCurrency(0);
            loadPipelineBoard(selectedPipelineId);

            InventMagApp.showToast(
                "Success",
                "Opportunity created successfully!",
                "success"
            );
        } catch (error) {
            console.error("Error creating opportunity:", error);
            InventMagApp.showToast(
                "Error",
                "Failed to create opportunity. Please try again.",
                "error"
            );
        }
    });
}