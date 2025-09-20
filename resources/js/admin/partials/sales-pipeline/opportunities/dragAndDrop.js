import { SALES_PIPELINE_ROUTES, CSRF_TOKEN } from '../common/constants.js';
import { loadPipelineBoard } from '../ui/pipelineBoard.js';

const pipelineBoard = document.getElementById("pipeline-board");

export async function moveOpportunity(opportunityId, newStageId, oldStageId) {
    try {
        const response = await fetch(
            `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}/move`,
            {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": CSRF_TOKEN,
                },
                body: JSON.stringify({
                    pipeline_stage_id: newStageId,
                }),
            }
        );

        if (!response.ok) {
            throw new Error("Failed to move opportunity");
        }

        updateStageCount(oldStageId, -1);
        updateStageCount(newStageId, 1);
    } catch (error) {
        console.error("Error moving opportunity:", error);
        window.showToast(
            "Error",
            "Failed to move opportunity. Please try again.",
            "error"
        );
        loadPipelineBoard(pipelineSelect.value);
    }
}

export function updateStageCount(stageId, change) {
    const stageColumn = pipelineBoard.querySelector(
        `[data-stage-id="${stageId}"]`
    );
    if (stageColumn) {
        const cardHeader = stageColumn
            .closest(".card-stacked")
            .querySelector(".card-header");
        if (cardHeader) {
            const title = cardHeader.querySelector(".card-title");
            if (title) {
                const currentCount = parseInt(
                    title.textContent.match(/\((\d+)\)/)[1],
                    10
                );
                const newCount = currentCount + change;
                const stageName = title.textContent.split("(")[0].trim();
                title.textContent = `${stageName} (${newCount})`;
            }
        }
    }
}
