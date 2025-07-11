@extends('admin.layouts.base')

@section('title', 'Sales Pipeline')

@section('content')
    <div class="page-wrapper">
        <div class="container-xl">
            <div class="page-header d-print-none">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="page-title">
                            Sales Pipeline
                        </h2>
                    </div>
                    <div class="col-auto ms-auto d-print-none">
                        <div class="btn-list">
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal"
                                data-bs-target="#newOpportunityModal">
                                <i class="ti ti-plus me-2"></i>
                                New Opportunity
                            </a>
                            <a href="#" class="btn btn-primary d-sm-none btn-icon" data-bs-toggle="modal"
                                data-bs-target="#newOpportunityModal" aria-label="New Opportunity">
                                <i class="ti ti-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-body" data-initial-pipelines="{{ json_encode($pipelines) }}"
                data-initial-customers="{{ json_encode($customers) }}">
                <div class="row row-cards">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex mb-3">
                                    <div class="me-auto">
                                        <label for="pipelineSelect" class="form-label">Select Pipeline:</label>
                                        <select class="form-select" id="pipelineSelect"></select>
                                    </div>
                                    <div class="ms-auto">
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal"
                                            data-bs-target="#managePipelinesModal">
                                            <i class="ti ti-settings me-2"></i>Manage Pipelines
                                        </button>
                                    </div>
                                </div>

                                <div id="pipeline-board" class="row flex-nowrap overflow-auto pb-3">
                                    <!-- Pipeline stages will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Opportunity Modal -->
        <div class="modal modal-blur fade" id="newOpportunityModal" tabindex="-1"
            aria-labelledby="newOpportunityModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newOpportunityModalLabel">Create New Sales Opportunity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="newOpportunityForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="opportunityName" class="form-label">Opportunity Name</label>
                                <input type="text" class="form-control" id="opportunityName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityCustomer" class="form-label">Customer</label>
                                <select class="form-select" id="opportunityCustomer" name="customer_id" required>
                                    <!-- Customers will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityAmount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="opportunityAmount" name="amount"
                                    step="0.01">
                            </div>
                            <div class="mb-3">
                                <label for="opportunityExpectedCloseDate" class="form-label">Expected Close Date</label>
                                <input type="date" class="form-control" id="opportunityExpectedCloseDate"
                                    name="expected_close_date">
                            </div>
                            <div class="mb-3">
                                <label for="opportunityDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="opportunityDescription" name="description" rows="3"></textarea>
                            </div>
                            <input type="hidden" id="newOpportunityPipelineId" name="sales_pipeline_id">
                            <input type="hidden" id="newOpportunityStageId" name="pipeline_stage_id">
                            <input type="hidden" name="status" value="open">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Opportunity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manage Pipelines Modal -->
        <div class="modal modal-blur fade" id="managePipelinesModal" tabindex="-1"
            aria-labelledby="managePipelinesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="managePipelinesModalLabel">Manage Sales Pipelines</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="pipelineManagementTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pipelines-list-tab" data-bs-toggle="tab"
                                    data-bs-target="#pipelines-list" type="button" role="tab"
                                    aria-controls="pipelines-list" aria-selected="true">Pipelines</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="add-pipeline-tab" data-bs-toggle="tab"
                                    data-bs-target="#add-pipeline" type="button" role="tab"
                                    aria-controls="add-pipeline" aria-selected="false">Add New Pipeline</button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3">
                            <div class="tab-pane fade show active" id="pipelines-list" role="tabpanel"
                                aria-labelledby="pipelines-list-tab">
                                <div id="pipelinesListContainer">
                                    <!-- Pipelines will be listed here -->
                                </div>
                            </div>
                            <div class="tab-pane fade" id="add-pipeline" role="tabpanel"
                                aria-labelledby="add-pipeline-tab">
                                <form id="newPipelineForm">
                                    <div class="mb-3">
                                        <label for="newPipelineName" class="form-label">Pipeline Name</label>
                                        <input type="text" class="form-control" id="newPipelineName" name="name"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="newPipelineDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="newPipelineDescription" name="description" rows="3"></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="newPipelineIsDefault"
                                            name="is_default">
                                        <label class="form-check-label" for="newPipelineIsDefault">Set as Default</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Create Pipeline</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Pipeline Modal -->
        <div class="modal modal-blur fade" id="editPipelineModal" tabindex="-1"
            aria-labelledby="editPipelineModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPipelineModalLabel">Edit Sales Pipeline</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                            <form id="editPipelineForm">
                                <input type="hidden" id="editPipelineId" name="id">
                                <div class="mb-3">
                                    <label for="editPipelineName" class="form-label">Pipeline Name</label>
                                    <input type="text" class="form-control" id="editPipelineName" name="name"
                                        required>
                                </div>
                                <div class="mb-3">
                                    <label for="editPipelineDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="editPipelineDescription" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="editPipelineIsDefault"
                                        name="is_default">
                                    <label class="form-check-label" for="editPipelineIsDefault">Set as Default</label>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Pipeline</button>
                            </form>
                            <hr>
                            <h6>Pipeline Stages</h6>
                            <div id="pipelineStagesContainer" class="mb-3">
                                <!-- Stages will be loaded here -->
                            </div>
                            <form id="newStageForm" class="row g-2 align-items-end">
                                <input type="hidden" id="newStagePipelineId">
                                <div class="col-md-5">
                                    <label for="newStageName" class="form-label">Stage Name</label>
                                    <input type="text" class="form-control" id="newStageName" name="name"
                                        required>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newStageIsClosed"
                                            name="is_closed">
                                        <label class="form-check-label" for="newStageIsClosed">Is 'Closed' Stage (Won/Lost)?</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">Add Stage</button>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                        </div>
                </div>
            </div>
        </div>

        <!-- Edit Opportunity Modal -->
        <div class="modal modal-blur fade" id="editOpportunityModal" tabindex="-1"
            aria-labelledby="editOpportunityModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editOpportunityModalLabel">Edit Sales Opportunity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editOpportunityForm">
                        <div class="modal-body">
                            <input type="hidden" id="editOpportunityId" name="id">
                            <div class="mb-3">
                                <label for="editOpportunityName" class="form-label">Opportunity Name</label>
                                <input type="text" class="form-control" id="editOpportunityName" name="name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityCustomer" class="form-label">Customer</label>
                                <select class="form-select" id="editOpportunityCustomer" name="customer_id" required>
                                    <!-- Customers will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityAmount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="editOpportunityAmount" name="amount"
                                    step="0.01">
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityExpectedCloseDate" class="form-label">Expected Close
                                    Date</label>
                                <input type="date" class="form-control" id="editOpportunityExpectedCloseDate"
                                    name="expected_close_date">
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="editOpportunityDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityStatus" class="form-label">Status</label>
                                <select class="form-select" id="editOpportunityStatus" name="status" required>
                                    <option value="open">Open</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
                                </select>
                            </div>
                            <input type="hidden" id="editOpportunityPipelineId" name="sales_pipeline_id">
                            <input type="hidden" id="editOpportunityStageId" name="pipeline_stage_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
