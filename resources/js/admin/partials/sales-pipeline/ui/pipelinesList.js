import { allPipelines } from '../common/state.js';

const pipelinesListContainer = document.getElementById(
    "pipelinesListContainer"
);

export function renderPipelinesList() {
    if (!pipelinesListContainer) return;

    pipelinesListContainer.innerHTML = "";

    if (allPipelines.length === 0) {
        pipelinesListContainer.innerHTML =
            '<p class="text-muted">No pipelines available</p>';
        return;
    }

    allPipelines.forEach((pipeline) => {
        const pipelineCard = document.createElement("div");
        pipelineCard.className = "card mb-3";
        pipelineCard.innerHTML = `
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title">${pipeline.name} ${
            pipeline.is_default
                ? '<span class="badge bg-primary text-white">Default</span>'
                : ""
        }</h5>
                        <p class="card-text">${
                            pipeline.description || "No description"
                        }</p>
                        <small class="text-muted">Stages: ${
                            pipeline.stages ? pipeline.stages.length : 0
                        }</small>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm edit-pipeline-btn"
                                    data-pipeline-id="${pipeline.id}">
                                <i class="ti ti-edit"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-pipeline-btn"
                                    data-pipeline-id="${pipeline.id}">
                                <i class="ti ti-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        pipelinesListContainer.appendChild(pipelineCard);
    });
}
