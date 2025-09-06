import { initializeData } from './partials/sales-pipeline/data/init.js';
import { initNewOpportunityForm } from './partials/sales-pipeline/forms/newOpportunity.js';
import { initNewPipelineForm } from './partials/sales-pipeline/forms/newPipeline.js';
import { initEditPipelineForm } from './partials/sales-pipeline/forms/editPipeline.js';
import { initNewStageForm } from './partials/sales-pipeline/forms/newStage.js';
import { initEditOpportunityForm, loadEditOpportunityModal } from './partials/sales-pipeline/forms/editOpportunity.js';
import { initConvertOpportunityForm } from './partials/sales-pipeline/forms/convertOpportunity.js';
import { showConfirmationModal } from './partials/sales-pipeline/common/utils.js';
import { SALES_PIPELINE_ROUTES, CSRF_TOKEN } from './partials/sales-pipeline/common/constants.js';
import { allPipelines } from './partials/sales-pipeline/common/state.js';
import { loadPipelineBoard } from './partials/sales-pipeline/ui/pipelineBoard.js';
import { renderPipelineStages } from './partials/sales-pipeline/ui/stagesList.js';

const pipelineSelect = document.getElementById("pipelineSelect");
const newStagePipelineId = document.getElementById("newStagePipelineId");

document.addEventListener("DOMContentLoaded", function () {
    initializeData();

    initNewOpportunityForm();
    initNewPipelineForm();
    initEditPipelineForm();
    initNewStageForm();
    initEditOpportunityForm();
    initConvertOpportunityForm();

    flatpickr("#opportunityExpectedCloseDate", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        allowInput: true,
    });
    flatpickr("#editOpportunityExpectedCloseDate", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        allowInput: true,
    });

    pipelineSelect?.addEventListener("change", function () {
        const selectedPipelineId = this.value;
        if (selectedPipelineId) {
            loadPipelineBoard(selectedPipelineId);
        }
    });

    document.addEventListener("click", async function (e) {
        if (e.target.closest(".edit-pipeline-btn")) {
            const pipelineId =
                e.target.closest(".edit-pipeline-btn").dataset.pipelineId;
            const pipeline = allPipelines.find((p) => p.id == pipelineId);
            if (pipeline) {
                document.getElementById("editPipelineId").value = pipeline.id;
                document.getElementById("editPipelineName").value =
                    pipeline.name;
                document.getElementById("editPipelineDescription").value =
                    pipeline.description || "";
                document.getElementById("editPipelineIsDefault").checked =
                    pipeline.is_default || false;

                newStagePipelineId.value = pipeline.id;

                renderPipelineStages(pipelineId);

                new bootstrap.Modal(
                    document.getElementById("editPipelineModal")
                ).show();
            }
        }

        if (e.target.closest(".delete-pipeline-btn")) {
            const pipelineId = e.target.closest(".delete-pipeline-btn").dataset
                .pipelineId;
            const confirmed = await showConfirmationModal(
                "Delete Pipeline",
                "Are you sure you want to delete this pipeline?"
            );
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}`,
                        {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Failed to delete pipeline");
                    }

                    await fetchData();
                    window.showToast(
                        "Success",
                        "Pipeline deleted successfully!",
                        "success"
                    );
                } catch (error) {
                    console.error("Error deleting pipeline:", error);
                    window.showToast(
                        "Error",
                        "Failed to delete pipeline. Please try again.",
                        "error"
                    );
                }
            }
        }

        if (e.target.closest(".delete-stage-btn")) {
            const stageId =
                e.target.closest(".delete-stage-btn").dataset.stageId;
            const confirmed = await showConfirmationModal(
                "Delete Stage",
                "Are you sure you want to delete this stage?"
            );
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.stagesBaseUrl}/${stageId}`,
                        {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Failed to delete stage");
                    }

                    await fetchData();
                    const pipelineId = newStagePipelineId.value;
                    if (pipelineId) {
                        renderPipelineStages(pipelineId);
                    }

                    window.showToast(
                        "Success",
                        "Stage deleted successfully!",
                        "success"
                    );
                } catch (error) {
                    console.error("Error deleting stage:", error);
                    window.showToast(
                        "Error",
                        "Failed to delete stage. Please try again.",
                        "error"
                    );
                }
            }
        }

        if (e.target.closest(".edit-opportunity-btn")) {
            const opportunityId = e.target.closest(".edit-opportunity-btn")
                .dataset.opportunityId;
            loadEditOpportunityModal(opportunityId);
        }

        if (e.target.closest(".delete-opportunity-btn")) {
            const opportunityId = e.target.closest(".delete-opportunity-btn")
                .dataset.opportunityId;
            const confirmed = await showConfirmationModal(
                "Delete Opportunity",
                "Are you sure you want to delete this opportunity?"
            );
            if (confirmed) {
                try {
                    const response = await fetch(
                        `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`,
                        {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": CSRF_TOKEN,
                            },
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Failed to delete opportunity");
                    }

                    loadPipelineBoard(pipelineSelect.value);
                    window.showToast(
                        "Success",
                        "Opportunity deleted successfully!",
                        "success"
                    );
                } catch (error) {
                    console.error("Error deleting opportunity:", error);
                    window.showToast(
                        "Error",
                        "Failed to delete opportunity. Please try again.",
                        "error"
                    );
                }
            }
        }

        if (e.target.closest(".convert-opportunity-btn")) {
            const opportunityId = e.target.closest(".convert-opportunity-btn")
                .dataset.opportunityId;
            const convertModal = new bootstrap.Modal(
                document.getElementById("convertOpportunityModal")
            );
            document.getElementById("convertOpportunityId").value =
                opportunityId;
            document.getElementById(
                "convertOpportunityForm"
            ).action = `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}/convert`;
            convertModal.show();
        }
    });
});