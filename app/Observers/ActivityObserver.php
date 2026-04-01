<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;

/**
 * Generic observer that can be reused for any model.
 * Register it in AppServiceProvider with the module name.
 */
class ActivityObserver
{
    protected ActivityLogService $logService;
    protected ?string $module = null;

    public function __construct(ActivityLogService $logService)
    {
        $this->logService = $logService;
    }

    public function setModule(string $module): self
    {
        $this->module = $module;
        return $this;
    }

    public function created($model): void
    {
        try {
            $label = $this->guessLabel($model);
            $this->logService->logCreate(
                $this->module,
                "Created {$this->module}" . ($label ? ": {$label}" : " #{$model->id}"),
                $model,
                $model->toArray()
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ActivityObserver::created failed for {$this->module}: " . $e->getMessage());
        }
    }

    public function updated($model): void
    {
        try {
            $label    = $this->guessLabel($model);
            $oldValues = $model->getOriginal();
            $newValues = $model->getChanges();

            if (empty($newValues)) {
                return; // nothing actually changed
            }

            $this->logService->logUpdate(
                $this->module,
                "Updated {$this->module}" . ($label ? ": {$label}" : " #{$model->id}"),
                $model,
                $oldValues,
                $newValues
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ActivityObserver::updated failed for {$this->module}: " . $e->getMessage());
        }
    }

    public function deleted($model): void
    {
        try {
            $label = $this->guessLabel($model);
            $this->logService->logDelete(
                $this->module,
                "Deleted {$this->module}" . ($label ? ": {$label}" : " #{$model->id}"),
                $model,
                $model->toArray()
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ActivityObserver::deleted failed for {$this->module}: " . $e->getMessage());
        }
    }

    protected function guessLabel($model): ?string
    {
        foreach (['name', 'title', 'invoice', 'code', 'email'] as $col) {
            if (isset($model->{$col}) && !empty($model->{$col})) {
                return (string) $model->{$col};
            }
        }
        return null;
    }
}
