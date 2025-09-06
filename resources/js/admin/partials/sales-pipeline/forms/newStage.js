import { SALES_PIPELINE_ROUTES } from '../common/constants.js';
import { fetchData } from '../data/init.js';
import { allPipelines } from '../common/state.js';
import { renderPipelineStages } from '../ui/stagesList.js';
import { loadPipelineBoard } from '../ui/pipelineBoard.js';

const newStageForm = document.getElementById("newStageForm");
const newStagePipelineId = document.getElementById("newStagePipelineId");

export function initNewStageForm() {
    newStageForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        const pipelineId = newStagePipelineId.value;

        if (!pipelineId) {
            window.showToast(
                "Error",
                "Pipeline ID is missing. Please close and reopen the modal.",
                "error"
            );
            return;
        }

        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        data.is_closed = formData.has("is_closed");

        if (!data.name) {
            window.showToast(
                "Error",
                "Please fill in all required fields.",
                "error"
            );
            return;
        }

        try {
            const response = await fetch(
                `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}/stages`,
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
                throw new Error(
                    `Failed to create stage: ${
                        errorData.message || response.status
                    }`
                );
            }

            await fetchData();

            loadPipelineBoard(pipelineId);

            const updatedPipeline = allPipelines.find(
                (p) => p.id == pipelineId
            );
            if (updatedPipeline) {
                renderPipelineStages(updatedPipeline.id);
            }

            this.reset();

            window.showToast(
                "Success",
                "Stage created successfully!",
                "success"
            );
        } catch (error) {
            console.error("Error creating stage:", error);
            window.showToast(
                "Error",
                `Failed to create stage. Please try again.`,
                "error"
            );
        }
    });
}
