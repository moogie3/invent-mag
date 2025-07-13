@extends('admin.layouts.base')

@section('title', 'Sales Pipeline')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Sales Management
                        </div>
                        <h2 class="page-title">
                            Sales Pipeline
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="card">
                    <div class="row g-0">
                        <div class="col-12 d-flex flex-column">
                            <div class="row row-deck row-cards">
                                <div class="col-md-12">
                                    <div class="card card-primary">
                                        <div class="card-body border-bottom py-3"
                                            data-initial-pipelines="{{ json_encode($pipelines) }}"
                                            data-initial-customers="{{ json_encode($customers) }}"
                                            data-currency-symbol="{{ \App\Helpers\CurrencyHelper::getCurrencySymbol() }}">
                                            <div class="d-flex align-items-center justify-content-between mb-4">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-chart-line fs-1 me-3 text-primary"></i>
                                                    <div>
                                                        <h2 class="mb-1">
                                                            Sales Pipeline Management
                                                        </h2>
                                                        <div class="text-muted">
                                                            Track and manage your sales opportunities
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-outline-primary" data-bs-toggle="modal"
                                                        data-bs-target="#managePipelinesModal">
                                                        <i class="ti ti-settings me-2"></i>Manage Pipelines
                                                    </button>
                                                    <button class="btn btn-primary d-none d-sm-inline-block"
                                                        data-bs-toggle="modal" data-bs-target="#newOpportunityModal">
                                                        <i class="ti ti-plus me-2"></i>New Opportunity
                                                    </button>
                                                    <button class="btn btn-primary d-sm-none btn-icon"
                                                        data-bs-toggle="modal" data-bs-target="#newOpportunityModal"
                                                        aria-label="New Opportunity">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <div class="card border-0 bg-light">
                                                        <div class="card-body py-3">
                                                            <div class="mb-2">
                                                                <label for="pipelineSelect"
                                                                    class="form-label text-muted mb-2 d-block">
                                                                    Active Pipeline
                                                                </label>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-3 d-flex align-items-center justify-content-center"
                                                                    style="width: 32px; height: 32px;">
                                                                    <i class="ti ti-target fs-3 text-success"></i>
                                                                </div>
                                                                <div class="flex-grow-1">
                                                                    <select class="form-select form-select-lg"
                                                                        id="pipelineSelect">
                                                                        <option value="">Select Pipeline</option>
                                                                        <!-- Options will be populated here -->
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card border-0 bg-primary text-white">
                                                        <div class="card-body py-3">
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-3">
                                                                    <i class="ti ti-currency-dollar fs-2"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-white-50 small">Total Pipeline Value
                                                                    </div>
                                                                    <div class="h3 mb-0" id="pipelineValue">
                                                                        {{ \App\Helpers\CurrencyHelper::getCurrencySymbol() }}0
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="ti ti-layout-kanban fs-4 me-2 text-primary"></i>
                                                <h4 class="mb-0">Pipeline Board</h4>
                                            </div>

                                            <div class="border rounded p-3 bg-light">
                                                <div id="pipeline-board" class="row flex-nowrap overflow-auto pb-3">
                                                    <!-- Pipeline stages will be loaded here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                        <h5 class="modal-title" id="newOpportunityModalLabel">
                            <i class="ti ti-plus me-2"></i>Create New Sales Opportunity
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="newOpportunityForm">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="opportunityName" class="form-label">
                                    <i class="ti ti-target me-1"></i>Opportunity Name
                                </label>
                                <input type="text" class="form-control" id="opportunityName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityCustomer" class="form-label">
                                    <i class="ti ti-user me-1"></i>Customer
                                </label>
                                <select class="form-select" id="opportunityCustomer" name="customer_id" required>
                                    <!-- Customers will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="ti ti-package me-1"></i>Products
                                </label>
                                <div id="newOpportunityItemsContainer">
                                    <!-- Product items will be added here dynamically -->
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addNewOpportunityItem">
                                    <i class="ti ti-plus me-2"></i>Add Product
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="newOpportunityTotalAmount" class="form-label">
                                    <i class="ti ti-currency-dollar me-1"></i>Total Amount
                                </label>
                                <input type="text" class="form-control bg-light" id="newOpportunityTotalAmount"
                                    readonly>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityExpectedCloseDate" class="form-label">
                                    <i class="ti ti-calendar me-1"></i>Expected Close Date
                                </label>
                                <input type="date" class="form-control" id="opportunityExpectedCloseDate"
                                    name="expected_close_date">
                            </div>
                            <div class="mb-3">
                                <label for="opportunityDescription" class="form-label">
                                    <i class="ti ti-notes me-1"></i>Description
                                </label>
                                <textarea class="form-control" id="opportunityDescription" name="description" rows="3"></textarea>
                            </div>
                            <input type="hidden" id="newOpportunityPipelineId" name="sales_pipeline_id">
                            <input type="hidden" id="newOpportunityStageId" name="pipeline_stage_id">
                            <input type="hidden" name="status" value="open">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>Save Opportunity
                            </button>
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
                        <h5 class="modal-title" id="managePipelinesModalLabel">
                            <i class="ti ti-settings me-2"></i>Manage Sales Pipelines
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="pipelineManagementTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pipelines-list-tab" data-bs-toggle="tab"
                                    data-bs-target="#pipelines-list" type="button" role="tab"
                                    aria-controls="pipelines-list" aria-selected="true">
                                    <i class="ti ti-list me-2"></i>Pipelines
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="add-pipeline-tab" data-bs-toggle="tab"
                                    data-bs-target="#add-pipeline" type="button" role="tab"
                                    aria-controls="add-pipeline" aria-selected="false">
                                    <i class="ti ti-plus me-2"></i>Add New Pipeline
                                </button>
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
                                        <label for="newPipelineName" class="form-label">
                                            <i class="ti ti-chart-line me-1"></i>Pipeline Name
                                        </label>
                                        <input type="text" class="form-control" id="newPipelineName" name="name"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="newPipelineDescription" class="form-label">
                                            <i class="ti ti-notes me-1"></i>Description
                                        </label>
                                        <textarea class="form-control" id="newPipelineDescription" name="description" rows="3"></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="newPipelineIsDefault"
                                            name="is_default">
                                        <label class="form-check-label" for="newPipelineIsDefault">
                                            <i class="ti ti-star me-1"></i>Set as Default
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-check me-2"></i>Create Pipeline
                                    </button>
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
                        <h5 class="modal-title" id="editPipelineModalLabel">
                            <i class="ti ti-edit me-2"></i>Edit Sales Pipeline
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editPipelineForm">
                            <input type="hidden" id="editPipelineId" name="id">
                            <div class="mb-3">
                                <label for="editPipelineName" class="form-label">
                                    <i class="ti ti-chart-line me-1"></i>Pipeline Name
                                </label>
                                <input type="text" class="form-control" id="editPipelineName" name="name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editPipelineDescription" class="form-label">
                                    <i class="ti ti-notes me-1"></i>Description
                                </label>
                                <textarea class="form-control" id="editPipelineDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="editPipelineIsDefault"
                                    name="is_default">
                                <label class="form-check-label" for="editPipelineIsDefault">
                                    <i class="ti ti-star me-1"></i>Set as Default
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>Update Pipeline
                            </button>
                        </form>
                        <hr>
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-layout-list me-2"></i>
                            <h6 class="mb-0">Pipeline Stages</h6>
                        </div>
                        <div id="pipelineStagesContainer" class="mb-3">
                            <!-- Stages will be loaded here -->
                        </div>
                        <form id="newStageForm" class="row g-2 align-items-end">
                            <input type="hidden" id="newStagePipelineId">
                            <div class="col-md-5">
                                <label for="newStageName" class="form-label">
                                    <i class="ti ti-flag me-1"></i>Stage Name
                                </label>
                                <input type="text" class="form-control" id="newStageName" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newStageIsClosed"
                                        name="is_closed">
                                    <label class="form-check-label" for="newStageIsClosed">
                                        <i class="ti ti-lock me-1"></i>Is 'Closed' Stage (Won/Lost)?
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ti ti-plus me-2"></i>Add Stage
                                </button>
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
                        <h5 class="modal-title" id="editOpportunityModalLabel">
                            <i class="ti ti-edit me-2"></i>Edit Sales Opportunity
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editOpportunityForm">
                        <div class="modal-body">
                            <input type="hidden" id="editOpportunityId" name="id">
                            <div class="mb-3">
                                <label for="editOpportunityName" class="form-label">
                                    <i class="ti ti-target me-1"></i>Opportunity Name
                                </label>
                                <input type="text" class="form-control" id="editOpportunityName" name="name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityCustomer" class="form-label">
                                    <i class="ti ti-user me-1"></i>Customer
                                </label>
                                <select class="form-select" id="editOpportunityCustomer" name="customer_id" required>
                                    <!-- Customers will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="ti ti-package me-1"></i>Products
                                </label>
                                <div id="editOpportunityItemsContainer">
                                    <!-- Product items will be added here dynamically -->
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    id="editNewOpportunityItem">
                                    <i class="ti ti-plus me-2"></i>Add Product
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityTotalAmount" class="form-label">
                                    <i class="ti ti-currency-dollar me-1"></i>Total Amount
                                </label>
                                <input type="text" class="form-control bg-light" id="editOpportunityTotalAmount"
                                    readonly>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityExpectedCloseDate" class="form-label">
                                    <i class="ti ti-calendar me-1"></i>Expected Close Date
                                </label>
                                <input type="date" class="form-control" id="editOpportunityExpectedCloseDate"
                                    name="expected_close_date">
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityDescription" class="form-label">
                                    <i class="ti ti-notes me-1"></i>Description
                                </label>
                                <textarea class="form-control" id="editOpportunityDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityStatus" class="form-label">
                                    <i class="ti ti-flag me-1"></i>Status
                                </label>
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
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div class="modal modal-blur fade" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-status bg-danger"></div>
                    <div class="modal-body text-center py-4">
                        <i class="ti ti-alert-triangle" style="font-size: 3rem;"></i>
                        <h3 id="confirmationModalTitle">Are you sure?</h3>
                        <div class="text-muted" id="confirmationModalBody">Do you really want to remove this item? This
                            action cannot be undone.</div>
                    </div>
                    <div class="modal-footer">
                        <div class="w-100">
                            <div class="row">
                                <div class="col">
                                    <a href="#" class="btn w-100" data-bs-dismiss="modal">
                                        Cancel
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="#" class="btn btn-danger w-100" id="confirmationModalConfirm">
                                        Confirm
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
