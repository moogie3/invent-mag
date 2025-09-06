import { SALES_PIPELINE_ROUTES } from '../common/constants.js';
import { allPipelines } from '../common/state.js';
import { formatCurrencyJs, getStatusColor } from '../common/utils.js';
import { createOpportunityCard } from '../opportunities/card.js';
import { moveOpportunity } from '../opportunities/dragAndDrop.js';

const pipelineBoard = document.getElementById("pipeline-board");

export async function loadPipelineBoard(pipelineId) {
    if (!pipelineBoard) return;

    pipelineBoard.innerHTML = "";
    const selectedPipeline = allPipelines.find((p) => p.id == pipelineId);
    if (!selectedPipeline) return;

    try {
        const cacheBuster = new Date().getTime();
        const opportunitiesResponse = await fetch(
            `${SALES_PIPELINE_ROUTES.opportunitiesIndex}?pipeline_id=${pipelineId}&_=${cacheBuster}`
        );
        if (!opportunitiesResponse.ok) {
            throw new Error("Failed to fetch opportunities");
        }
        const responseData = await opportunitiesResponse.json();
        const opportunities = responseData.opportunities;
        const totalPipelineValue = responseData.total_pipeline_value;

        const pipelineValueElement =
            document.getElementById("pipelineValue");
        if (pipelineValueElement) {
            pipelineValueElement.textContent =
                formatCurrencyJs(totalPipelineValue);
        }

        selectedPipeline.stages
            .sort((a, b) => a.position - b.position)
            .forEach((stage) => {
                const stageOpportunities = opportunities.filter(
                    (opp) => opp.pipeline_stage_id === stage.id
                );
                const stageColumn = document.createElement("div");
                stageColumn.className = "col-md-4";
                stageColumn.innerHTML = `
            <div class="card card-stacked">
                <div class="card-header">
                    <h3 class="card-title">${stage.name} (${stageOpportunities.length})</h3>
                </div>
                <div class="card-body p-2 stage-column" data-stage-id="${stage.id}" style="min-height: 150px;">
                    <!-- Opportunities will be dragged here -->
                </div>
            </div>
        `;
                const opportunitiesContainer =
                    stageColumn.querySelector(".stage-column");
                stageOpportunities.forEach((opportunity) => {
                    opportunitiesContainer.appendChild(
                        createOpportunityCard(opportunity)
                    );
                });
                pipelineBoard.appendChild(stageColumn);

                if (typeof Sortable !== "undefined") {
                    new Sortable(opportunitiesContainer, {
                        group: "opportunities",
                        animation: 150,
                        onEnd: function (evt) {
                            const opportunityId =
                                evt.item.dataset.opportunityId;
                            const oldStageId = evt.from.dataset.stageId;
                            const newStageId = evt.to.dataset.stageId;
                            if (oldStageId !== newStageId) {
                                moveOpportunity(
                                    opportunityId,
                                    newStageId,
                                    oldStageId
                                );
                            }
                        },
                    });
                }
            });
    } catch (error) {
        console.error("Error loading pipeline board:", error);
        pipelineBoard.innerHTML =
            '<div class="alert alert-danger">Error loading pipeline board</div>';
    }
}
