@extends('admin.layouts.base')

@section('title', __('messages.sales_pipeline'))

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            {{ __('messages.sales_management') }}
                        </div>
                        <h2 class="page-title d-flex align-items-center">
                            <i class="ti ti-chart-line me-2"></i>{{ __('messages.sales_pipeline') }}
                            <a href="#" class="ms-3 text-muted trigger-tutorial-modal" data-bs-toggle="tooltip" title="{{ __('messages.tutorial') }}">
                                <i class="ti ti-help-circle fs-3"></i>
                            </a>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="{{ $containerClass ?? "container-xl" }}">
                <div class="card">
                    <div class="row g-0">
                        <div class="col-12 d-flex flex-column">
                            <div class="row row-deck row-cards">
                                <div class="col-md-12">
                                    <div class="card card-primary">
                                        <div class="card-body border-bottom py-3"
                                            data-initial-pipelines="{{ json_encode($pipelines) }}"
                                            data-initial-customers="{{ json_encode($customers) }}"
                                            data-currency-symbol="{{ \App\Helpers\CurrencyHelper::getCurrencySymbol() }}"
                                            data-decimal-places="{{ \App\Models\CurrencySetting::first()->decimal_places ?? 0 }}"
                                             data-decimal-separator="{{ \App\Models\CurrencySetting::first()->decimal_separator ?? '.' }}"
                                             data-thousand-separator="{{ \App\Models\CurrencySetting::first()->thousand_separator ?? ',' }}"
                                             data-currency-code="{{ \App\Models\CurrencySetting::first()->currency_code ?? 'USD' }}"
                                             data-position="{{ \App\Models\CurrencySetting::first()->position ?? 'prefix' }}"
                                            >
                                            <div class="d-flex align-items-center justify-content-between mb-4">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-chart-line fs-1 me-3 text-primary"></i>
                                                    <div>
                                                        <h2 class="mb-1">
                                                            {{ __('messages.sales_pipeline_management') }}
                                                        </h2>
                                                        <div class="text-muted">
                                                            {{ __('messages.track_and_manage_your_sales_opportunities') }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-list">
                                                    <button class="btn btn-outline-primary" data-bs-toggle="modal"
                                                        data-bs-target="#managePipelinesModal">
                                                        <i class="ti ti-settings me-2"></i>{{ __('messages.manage_pipelines') }}
                                                    </button>
                                                    <button class="btn btn-primary d-none d-sm-inline-block"
                                                        data-bs-toggle="modal" data-bs-target="#newOpportunityModal">
                                                        <i class="ti ti-plus me-2"></i>{{ __('messages.new_opportunity') }}
                                                    </button>
                                                    <button class="btn btn-primary d-sm-none btn-icon"
                                                        data-bs-toggle="modal" data-bs-target="#newOpportunityModal"
                                                        aria-label="{{ __('messages.new_opportunity') }}">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <div class="btn-group ms-2" role="group">
                                                        <button type="button" class="btn btn-outline-secondary active" id="viewKanbanBtn" data-view="kanban">
                                                            <i class="ti ti-layout-kanban"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" id="viewListBtn" data-view="list">
                                                            <i class="ti ti-list"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <div class="card border-0 bg-body-secondary">
                                                        <div class="card-body py-3">
                                                            <div class="mb-2">
                                                                <label for="pipelineSelect"
                                                                    class="form-label text-muted mb-2 d-block">
                                                                    {{ __('messages.active_pipeline') }}
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
                                                                        <option value="">{{ __('messages.select_pipeline') }}</option>
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
                                                                    <i class="ti ti-currency fs-2"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-white-50 small">{{ __('messages.total_pipeline_value') }}
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
                                                <h4 class="mb-0">{{ __('messages.pipeline_board') }}</h4>
                                            </div>

                                            <div class="border rounded p-3 bg-body-secondary">
                                                @if (count($pipelines) == 0)
                                                    <div class="empty">
                                                        <div class="empty-img">
                                                            <i class="ti ti-mood-sad" style="font-size: 5rem; color: #ccc;"></i>
                                                        </div>
                                                        <p class="empty-title">{{ __('messages.no_sales_pipelines_found') }}</p>
                                                        <p class="empty-subtitle text-muted">
                                                            {{ __('messages.it_looks_like_you_havent_created_any_sales_pipelines_yet') }}
                                                        </p>
                                                        <div class="empty-action">
                                                            <button class="btn btn-primary" data-bs-toggle="modal"
                                                                data-bs-target="#managePipelinesModal">
                                                                <i class="ti ti-plus me-2"></i>{{ __('messages.create_your_first_pipeline') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div id="pipeline-board" class="row flex-nowrap overflow-auto pb-3">
                                                        <!-- Pipeline stages will be loaded here -->
                                                    </div>
                                                    <div id="pipeline-list-view" class="table-responsive" style="display: none;"
                                                         data-trans="{{ json_encode([
                                                            'no_opportunities' => __('messages.no_data'),
                                                            'error_loading' => __('messages.error_loading_data'),
                                                            'edit' => __('messages.edit'),
                                                            'print' => __('messages.print'),
                                                            'quote' => __('messages.quote'),
                                                            'convert' => __('messages.convert_to_sales_order'),
                                                            'delete' => __('messages.delete'),
                                                            'actions' => __('messages.actions'),
                                                            'open' => __('messages.open'),
                                                            'won' => __('messages.won'),
                                                            'lost' => __('messages.lost'),
                                                            'converted' => __('messages.converted'),
                                                        ]) }}">
                                                        <table class="table card-table table-vcenter" id="pipelineListTable">
                                                            <thead class="text-center">
                                                                <tr>
                                                                    <th>{{ __('messages.table_no') }}</th>
                                                                    <th>{{ __('messages.opportunity_name') }}</th>
                                                                    <th>{{ __('messages.customer') }}</th>
                                                                    <th>{{ __('messages.stage') }}</th>
                                                                    <th>{{ __('messages.amount') }}</th>
                                                                    <th>{{ __('messages.expected_close_date') }}</th>
                                                                    <th>{{ __('messages.status') }}</th>
                                                                    <th>{{ __('messages.table_action') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="text-center" id="pipelineListBody">
                                                                <tr><td colspan="8" class="text-muted py-4">{{ __('messages.select_pipeline') }}</td></tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif
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
                            <i class="ti ti-plus me-2"></i>{{ __('messages.create_new_sales_opportunity') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="newOpportunityForm" novalidate data-validate-form>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="opportunityName" class="form-label">
                                    <i class="ti ti-target me-1"></i>{{ __('messages.opportunity_name') }}
                                </label>
                                <input type="text" class="form-control" id="opportunityName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityCustomer" class="form-label">
                                    <i class="ti ti-user me-1"></i>{{ __('messages.customer') }}
                                </label>
                                <select class="form-select" id="opportunityCustomer" name="customer_id" required>
                                    <!-- Customers will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <span class="form-label d-block">
                                    <i class="ti ti-package me-1"></i>{{ __('messages.products') }}
                                </span>
                                <div id="newOpportunityItemsContainer">
                                    <!-- Product items will be added here dynamically -->
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addNewOpportunityItem">
                                    <i class="ti ti-plus me-2"></i>{{ __('messages.add_product') }}
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="newOpportunityTotalAmount" class="form-label">
                                    <i class="ti ti-currency-dollar me-1"></i>{{ __('messages.total_amount') }}
                                </label>
                                <input type="text" class="form-control bg-body-secondary" id="newOpportunityTotalAmount"
                                    readonly>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityExpectedCloseDate" class="form-label">
                                    <i class="ti ti-calendar me-1"></i>{{ __('messages.expected_close_date') }}
                                </label>
                                <input type="date" class="form-control" id="opportunityExpectedCloseDate"
                                    name="expected_close_date">
                            </div>
                            <div class="mb-3">
                                <label for="opportunityDescription" class="form-label">
                                    <i class="ti ti-notes me-1"></i>{{ __('messages.description') }}
                                </label>
                                <textarea class="form-control" id="opportunityDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityNotes" class="form-label">
                                    <i class="ti ti-file-text me-1"></i>{{ __('messages.notes') }}
                                </label>
                                <div class="p-3 bg-body-secondary rounded-3">
                                    <textarea class="form-control" id="opportunityNotes" name="notes" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="opportunityTermsConditions" class="form-label">
                                    <i class="ti ti-file-description me-1"></i>{{ __('messages.terms_conditions') }}
                                </label>
                                <div class="p-3 bg-body-secondary rounded-3">
                                    <textarea class="form-control" id="opportunityTermsConditions" name="terms_conditions" rows="3"></textarea>
                                </div>
                            </div>
                            <input type="hidden" id="newOpportunityPipelineId" name="sales_pipeline_id">
                            <input type="hidden" id="newOpportunityStageId" name="pipeline_stage_id">
                            <input type="hidden" name="status" value="open">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>{{ __('messages.save_changes') }}
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
                            <i class="ti ti-settings me-2"></i>{{ __('messages.manage_sales_pipelines') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" id="pipelineManagementTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pipelines-list-tab" data-bs-toggle="tab"
                                    data-bs-target="#pipelines-list" type="button" role="tab"
                                    aria-controls="pipelines-list" aria-selected="true">
                                    <i class="ti ti-list me-2"></i>{{ __('messages.pipelines') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="add-pipeline-tab" data-bs-toggle="tab"
                                    data-bs-target="#add-pipeline" type="button" role="tab"
                                    aria-controls="add-pipeline" aria-selected="false">
                                    <i class="ti ti-plus me-2"></i>{{ __('messages.add_new_pipeline') }}
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
                                <form id="newPipelineForm" novalidate data-validate-form>
                                    <div class="mb-3">
                                        <label for="newPipelineName" class="form-label">
                                            <i class="ti ti-chart-line me-1"></i>{{ __('messages.pipeline_name') }}
                                        </label>
                                        <input type="text" class="form-control" id="newPipelineName" name="name"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="newPipelineDescription" class="form-label">
                                            <i class="ti ti-notes me-1"></i>{{ __('messages.description') }}
                                        </label>
                                        <textarea class="form-control" id="newPipelineDescription" name="description" rows="3"></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="newPipelineIsDefault"
                                            name="is_default">
                                        <label class="form-check-label" for="newPipelineIsDefault">
                                            <i class="ti ti-star me-1"></i>{{ __('messages.set_as_default') }}
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-check me-2"></i>{{ __('messages.create_pipeline') }}
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
                            <i class="ti ti-edit me-2"></i>{{ __('messages.edit_sales_pipeline') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editPipelineForm" novalidate data-validate-form>
                            <input type="hidden" id="editPipelineId" name="id">
                            <div class="mb-3">
                                <label for="editPipelineName" class="form-label">
                                    <i class="ti ti-chart-line me-1"></i>{{ __('messages.pipeline_name') }}
                                </label>
                                <input type="text" class="form-control" id="editPipelineName" name="name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editPipelineDescription" class="form-label">
                                    <i class="ti ti-notes me-1"></i>{{ __('messages.description') }}
                                </label>
                                <textarea class="form-control" id="editPipelineDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="editPipelineIsDefault"
                                    name="is_default">
                                <label class="form-check-label" for="editPipelineIsDefault">
                                    <i class="ti ti-star me-1"></i>{{ __('messages.set_as_default') }}
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>{{ __('messages.update_pipeline') }}
                            </button>
                        </form>
                        <hr>
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-layout-list me-2"></i>
                            <h6 class="mb-0">{{ __('messages.pipeline_stages') }}</h6>
                        </div>
                        <div id="pipelineStagesContainer" class="mb-3">
                            <!-- Stages will be loaded here -->
                        </div>
                        <form id="newStageForm" class="row g-2 align-items-end" novalidate data-validate-form>
                            <input type="hidden" id="newStagePipelineId" name="pipeline_id">
                            <div class="col-md-5">
                                <label for="newStageName" class="form-label">
                                    <i class="ti ti-flag me-1"></i>{{ __('messages.stage_name') }}
                                </label>
                                <input type="text" class="form-control" id="newStageName" name="name" required>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="newStageIsClosed"
                                        name="is_closed">
                                    <label class="form-check-label" for="newStageIsClosed">
                                        <i class="ti ti-lock me-1"></i>{{ __('messages.is_closed_stage_won_lost') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ti ti-plus me-2"></i>{{ __('messages.add_stage') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
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
                            <i class="ti ti-edit me-2"></i>{{ __('messages.edit_sales_opportunity') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editOpportunityForm" novalidate data-validate-form>
                        <div class="modal-body">
                            <input type="hidden" id="editOpportunityId" name="id">
                            <input type="hidden" id="editOpportunityPipelineId" name="sales_pipeline_id">
                            <input type="hidden" id="editOpportunityStageId" name="pipeline_stage_id">
                            <div class="mb-3">
                                <label for="editOpportunityName" class="form-label">
                                    <i class="ti ti-target me-1"></i>{{ __('messages.opportunity_name') }}
                                </label>
                                <input type="text" class="form-control" id="editOpportunityName" name="name"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityCustomer" class="form-label">
                                    <i class="ti ti-user me-1"></i>{{ __('messages.customer') }}
                                </label>
                                <select class="form-select" id="editOpportunityCustomer" name="customer_id" required>
                                    <!-- Customers will be loaded here -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <span class="form-label d-block">
                                    <i class="ti ti-package me-1"></i>{{ __('messages.products') }}
                                </span>
                                <div id="editOpportunityItemsContainer">
                                    <!-- Product items will be added here dynamically -->
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    id="editNewOpportunityItem">
                                    <i class="ti ti-plus me-2"></i>{{ __('messages.add_product') }}
                                </button>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityTotalAmount" class="form-label">
                                    <i class="ti ti-currency-dollar me-1"></i>{{ __('messages.total_amount') }}
                                </label>
                                <input type="text" class="form-control bg-body-secondary" id="editOpportunityTotalAmount"
                                    readonly>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityExpectedCloseDate" class="form-label">
                                    <i class="ti ti-calendar me-1"></i>{{ __('messages.expected_close_date') }}
                                </label>
                                <input type="date" class="form-control" id="editOpportunityExpectedCloseDate"
                                    name="expected_close_date">
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityDescription" class="form-label">
                                    <i class="ti ti-notes me-1"></i>{{ __('messages.description') }}
                                </label>
                                <textarea class="form-control" id="editOpportunityDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityNotes" class="form-label">
                                    <i class="ti ti-file-text me-1"></i>{{ __('messages.notes') }}
                                </label>
                                <div class="p-3 bg-body-secondary rounded-3">
                                    <textarea class="form-control" id="editOpportunityNotes" name="notes" rows="3">{{ old('notes', $opportunity->notes ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityTermsConditions" class="form-label">
                                    <i class="ti ti-file-description me-1"></i>{{ __('messages.terms_conditions') }}
                                </label>
                                <div class="p-3 bg-body-secondary rounded-3">
                                    <textarea class="form-control" id="editOpportunityTermsConditions" name="terms_conditions" rows="3">{{ old('terms_conditions', $opportunity->terms_conditions ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="editOpportunityStatus" class="form-label">
                                    <i class="ti ti-flag me-1"></i>{{ __('messages.status') }}
                                </label>
                                <select class="form-select" id="editOpportunityStatus" name="status" required>
                                    <option value="open">{{ __('messages.open') }}</option>
                                    <option value="won">{{ __('messages.won') }}</option>
                                    <option value="lost">{{ __('messages.lost') }}</option>
                                </select>
                            </div>
                            <input type="hidden" id="editOpportunityPipelineId" name="sales_pipeline_id">
                            <input type="hidden" id="editOpportunityStageId" name="pipeline_stage_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" data-bs-dismiss="modal">{{ __('messages.close') }}</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-2"></i>{{ __('messages.save_changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
    </div>
@include('admin.layouts.modals.sales.sales-pipeline-modals')
@endsection


