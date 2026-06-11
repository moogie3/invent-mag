import {
    initializeData,
    fetchData,
} from "./partials/sales-pipeline/data/init.js";
import { initNewOpportunityForm } from "./partials/sales-pipeline/forms/newOpportunity.js";
import { initNewPipelineForm } from "./partials/sales-pipeline/forms/newPipeline.js";
import { initEditPipelineForm } from "./partials/sales-pipeline/forms/editPipeline.js";
import { initNewStageForm } from "./partials/sales-pipeline/forms/newStage.js";
import {
    initEditOpportunityForm,
    loadEditOpportunityModal,
} from "./partials/sales-pipeline/forms/editOpportunity.js";
import { initConvertOpportunityForm } from "./partials/sales-pipeline/forms/convertOpportunity.js";
import { showConfirmationModal } from "./partials/sales-pipeline/common/utils.js";
import {
    SALES_PIPELINE_ROUTES,
    CSRF_TOKEN,
} from "./partials/sales-pipeline/common/constants.js";
import { allPipelines } from "./partials/sales-pipeline/common/state.js";
import { loadPipelineBoard } from "./partials/sales-pipeline/ui/pipelineBoard.js";
import { renderPipelineStages } from "./partials/sales-pipeline/ui/stagesList.js";
import {
    createProductItemRow,
    calculateTotalAmount,
} from "./partials/sales-pipeline/modals/opportunityItems.js";

import { onPageLoad } from './utils/onPageLoad.js';
import { formatCurrency } from '../utils/currencyFormatter.js';

onPageLoad(function () {
    if (!window.location.pathname.includes("/admin/sales-pipeline")) return;

    const pipelineSelect = document.getElementById("pipelineSelect");
    const newStagePipelineId = document.getElementById("newStagePipelineId");

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

    // Kanban / List view toggle
    const kanbanBtn = document.getElementById('viewKanbanBtn');
    const listBtn = document.getElementById('viewListBtn');
    const kanbanView = document.getElementById('pipeline-board');
    const listView = document.getElementById('pipeline-list-view');

    if (kanbanBtn && listBtn && kanbanView && listView) {
        function setActiveView(view) {
            if (view === 'list') {
                kanbanView.style.display = 'none';
                listView.style.display = '';
                kanbanBtn.classList.remove('active');
                listBtn.classList.add('active');
                populateListView();
            } else {
                kanbanView.style.display = '';
                listView.style.display = 'none';
                listBtn.classList.remove('active');
                kanbanBtn.classList.add('active');
            }
        }

        async function populateListView() {
            const pipelineId = document.getElementById('pipelineSelect')?.value;
            if (!pipelineId) return;

            const listView = document.getElementById('pipeline-list-view');
            const trans = listView?.dataset?.trans ? JSON.parse(listView.dataset.trans) : {};

            try {
                const response = await fetch(`/admin/sales-pipeline/opportunities?pipeline_id=${pipelineId}`);
                if (!response.ok) throw new Error('Failed to fetch');
                const data = await response.json();
                const tbody = document.getElementById('pipelineListBody');
                if (!data.opportunities || data.opportunities.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-muted py-4">${trans.no_opportunities || 'No data available'}</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.opportunities.map((opp, i) => {
                    const stageName = opp.stage?.name || 'N/A';
                    const customerName = opp.customer?.name || 'N/A';
                    const amount = formatCurrency(opp.amount);
                    const closeDate = opp.expected_close_date ? new Date(opp.expected_close_date).toLocaleDateString('en-GB') : '-';
                    const statusText = trans[opp.status] || opp.status.charAt(0).toUpperCase() + opp.status.slice(1);
                    const statusBadge = opp.status === 'won' ? 'bg-success text-white' : opp.status === 'lost' ? 'bg-danger text-white' : opp.status === 'converted' ? 'bg-info text-white' : 'bg-secondary text-white';
                    return `<tr>
                        <td>${i + 1}</td>
                        <td><a href="#" class="edit-opportunity-btn text-reset fw-semibold" data-opportunity-id="${opp.id}">${opp.name}</a></td>
                        <td>${customerName}</td>
                        <td>${stageName}</td>
                        <td>${amount}</td>
                        <td>${closeDate}</td>
                        <td><span class="badge rounded-pill ${statusBadge}">${statusText}</span></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown" data-bs-boundary="viewport">${trans.actions || 'Actions'}</button>
                                <div class="dropdown-menu">
                                    <button class="dropdown-item edit-opportunity-btn" data-opportunity-id="${opp.id}"><i class="ti ti-edit me-2"></i>${trans.edit || 'Edit'}</button>
                                    <a class="dropdown-item" href="/admin/sales-pipeline/opportunities/${opp.id}/print" target="_blank"><i class="ti ti-printer me-2"></i>${trans.print || 'Print'}</a>
                                    ${opp.status !== 'converted' && opp.items && opp.items.length > 0 ? `<a class="dropdown-item" href="/admin/sales-pipeline/opportunities/${opp.id}/generate-quotation"><i class="ti ti-file-description me-2"></i>${trans.quote || 'Quote'}</a>` : ''}
                                    ${opp.status === 'won' && !opp.sales_id ? `<button class="dropdown-item text-success convert-opportunity-btn" data-opportunity-id="${opp.id}"><i class="ti ti-check me-2"></i>${trans.convert || 'Convert'}</button>` : ''}
                                    <button class="dropdown-item text-danger delete-opportunity-btn" data-opportunity-id="${opp.id}"><i class="ti ti-trash me-2"></i>${trans.delete || 'Delete'}</button>
                                </div>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            } catch {
                document.getElementById('pipelineListBody').innerHTML = `<tr><td colspan="8" class="text-muted py-4">${trans.error_loading || 'Error loading data'}</td></tr>`;
            }
        }

        kanbanBtn.addEventListener('click', () => setActiveView('kanban'));
        listBtn.addEventListener('click', () => setActiveView('list'));
    }

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
            const actionUrl = `${SALES_PIPELINE_ROUTES.pipelinesBaseUrl}/${pipelineId}`;
            const confirmed = await showConfirmationModal(actionUrl);
            // The form submission in the modal will handle the deletion and page refresh
            // No need for explicit fetch call here anymore
        }

        if (e.target.closest(".delete-stage-btn")) {
            const stageId =
                e.target.closest(".delete-stage-btn").dataset.stageId;
            const actionUrl = `${SALES_PIPELINE_ROUTES.stagesBaseUrl}/${stageId}`;
            const confirmed = await showConfirmationModal(actionUrl);
            // The form submission in the modal will handle the deletion and page refresh
            // No need for explicit fetch call here anymore
        }

        if (e.target.closest(".edit-opportunity-btn")) {
            const opportunityId = e.target.closest(".edit-opportunity-btn")
                .dataset.opportunityId;
            loadEditOpportunityModal(opportunityId);
        }

        if (e.target.closest(".delete-opportunity-btn")) {
            const opportunityId = e.target.closest(".delete-opportunity-btn")
                .dataset.opportunityId;
            const actionUrl = `${SALES_PIPELINE_ROUTES.opportunitiesBaseUrl}/${opportunityId}`;
            const confirmed = await showConfirmationModal(actionUrl);
            // The form submission in the modal will handle the deletion and page refresh
            // No need for explicit fetch call here anymore
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

        if (e.target.matches("#addNewOpportunityItem")) {
            const container = document.getElementById(
                "newOpportunityItemsContainer"
            );
            const index = container.children.length;
            const newItemRow = createProductItemRow({}, index, container.id);
            container.appendChild(newItemRow);
        }

        if (e.target.matches("#editNewOpportunityItem")) {
            const container = document.getElementById(
                "editOpportunityItemsContainer"
            );
            const index = container.children.length;
            const newItemRow = createProductItemRow({}, index, container.id);
            container.appendChild(newItemRow);
        }
    });

    window.shortcutManager.register(
        "alt+n",
        () => {
            const newOpportunityModal = new bootstrap.Modal(
                document.getElementById("newOpportunityModal")
            );
            newOpportunityModal.show();
        },
        "New Opportunity"
    );

    window.shortcutManager.register(
        "ctrl+s",
        () => {
            const modals = [
                "newOpportunityModal",
                "managePipelinesModal",
                "editPipelineModal",
                "editOpportunityModal",
            ];

            for (const modalId of modals) {
                const modal = document.getElementById(modalId);
                if (modal && modal.classList.contains("show")) {
                    const form = modal.querySelector("form");
                    if (form) {
                        form.requestSubmit();
                        break;
                    }
                }
            }
        },
        "Save Form"
    );
}, { id: 'sales-pipeline' });
