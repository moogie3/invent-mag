<?php

use App\Http\Controllers\Admin\SalesPipelineController;

// Sales Pipeline Routes - Protected by plan feature + sales pipeline permissions
Route::middleware(['plan.feature:crm', 'can:view-sales-pipeline'])->group(function () {
    Route::get('/sales-pipeline', [SalesPipelineController::class, 'index'])->name('admin.sales_pipeline.index');

    Route::prefix('sales-pipeline')->group(function () {
        // View routes
        Route::get('/pipelines', [SalesPipelineController::class, 'indexPipelines'])->name('admin.sales_pipeline.pipelines.index');
        Route::get('/opportunities', [SalesPipelineController::class, 'indexOpportunities'])->name('admin.sales_pipeline.opportunities.index');
        Route::get('/opportunities/{opportunity}', [SalesPipelineController::class, 'showOpportunity'])->name('admin.sales_pipeline.opportunities.show');
        Route::get('/opportunities/{opportunity}/convert', [SalesPipelineController::class, 'showConvertForm'])->name('admin.sales_pipeline.opportunities.convert.show');
        Route::get('/opportunities/{opportunity}/print', [SalesPipelineController::class, 'print'])->name('admin.sales_pipeline.opportunities.print');
        Route::get('/opportunities/{opportunity}/generate-quotation', [SalesPipelineController::class, 'generateQuotation'])->name('admin.sales_pipeline.opportunities.generate-quotation');
        Route::post('/export', [SalesPipelineController::class, 'exportAll'])->name('admin.sales-pipeline.export');

        // Pipeline management routes
        Route::middleware('can:manage-sales-pipelines')->group(function () {
            Route::post('/pipelines', [SalesPipelineController::class, 'storePipeline'])->name('admin.sales_pipeline.pipelines.store');
            Route::put('/pipelines/{pipeline}', [SalesPipelineController::class, 'updatePipeline'])->name('admin.sales_pipeline.pipelines.update');
            Route::delete('/pipelines/{pipeline}', [SalesPipelineController::class, 'destroyPipeline'])->name('admin.sales_pipeline.pipelines.destroy');
        });

        // Stage management routes
        Route::middleware('can:manage-pipeline-stages')->group(function () {
            Route::post('/pipelines/{pipeline}/stages', [SalesPipelineController::class, 'storeStage'])->name('admin.sales_pipeline.stages.store');
            Route::put('/stages/{stage}', [SalesPipelineController::class, 'updateStage'])->name('admin.sales_pipeline.stages.update');
            Route::delete('/stages/{stage}', [SalesPipelineController::class, 'destroyStage'])->name('admin.sales_pipeline.stages.destroy');
            Route::post('/pipelines/{pipeline}/stages/reorder', [SalesPipelineController::class, 'reorderStages'])->name('admin.sales_pipeline.stages.reorder');
        });

        // Opportunity management routes
        Route::middleware('can:manage-sales-opportunities')->group(function () {
            Route::post('/opportunities', [SalesPipelineController::class, 'storeOpportunity'])->name('admin.sales_pipeline.opportunities.store');
            Route::post('/opportunities/{opportunity}', [SalesPipelineController::class, 'updateOpportunity'])->name('admin.sales_pipeline.opportunities.update');
            Route::delete('/opportunities/{opportunity}', [SalesPipelineController::class, 'destroyOpportunity'])->name('admin.sales_pipeline.opportunities.destroy');
            Route::put('/opportunities/{opportunity}/move', [SalesPipelineController::class, 'moveOpportunity'])->name('admin.sales_pipeline.opportunities.move');
            Route::post('/opportunities/{opportunity}/convert', [SalesPipelineController::class, 'convertToSalesOrder'])->name('admin.sales_pipeline.opportunities.convert');
        });
    });
});
