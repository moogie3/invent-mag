import { allPipelines } from '../common/state.js';
import { loadPipelineBoard } from './pipelineBoard.js';

const pipelineSelect = document.getElementById("pipelineSelect");

export function renderPipelinesSelect(shouldLoadBoard = true) {
    if (!pipelineSelect) return;

    pipelineSelect.innerHTML = '<option value="">Select Pipeline</option>';

    if (allPipelines.length === 0) {
        const option = document.createElement("option");
        option.value = "";
        option.textContent = "No pipelines available";
        option.disabled = true;
        pipelineSelect.appendChild(option);
        return;
    }

    allPipelines.forEach((pipeline) => {
        const option = document.createElement("option");
        option.value = pipeline.id;
        option.textContent = pipeline.name;
        pipelineSelect.appendChild(option);
    });

    const defaultPipeline =
        allPipelines.find((p) => p.is_default) || allPipelines[0];
    if (defaultPipeline) {
        pipelineSelect.value = defaultPipeline.id;
        if (shouldLoadBoard) {
            loadPipelineBoard(defaultPipeline.id);
        }
    }
}
