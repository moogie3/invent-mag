import { allPipelines } from '../common/state.js';

const pipelineStagesContainer = document.getElementById(
    "pipelineStagesContainer"
);

export function renderPipelineStages(pipelineId) {
    if (!pipelineStagesContainer) return;

    const pipeline = allPipelines.find((p) => p.id == pipelineId);
    if (!pipeline || !pipeline.stages) {
        pipelineStagesContainer.innerHTML =
            '<p class="text-muted">No stages available</p>';
        return;
    }

    pipelineStagesContainer.innerHTML = "";

    pipeline.stages
        .sort((a, b) => a.position - b.position)
        .forEach((stage) => {
            const stageCard = document.createElement("div");
            stageCard.className = "card mb-2";
            stageCard.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col">
                        <strong>${stage.name}</strong>
                        <small class="text-muted">Position: ${
                            stage.position
                        }</small>
                        ${
                            stage.is_closed
                                ? '<span class="badge bg-success text-white ms-2">Closed Stage</span>'
                                : ""
                        }
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-danger btn-sm delete-stage-btn"
                                data-stage-id="${stage.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
            pipelineStagesContainer.appendChild(stageCard);
        });
}
