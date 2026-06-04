<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Sales;
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

    protected function resolveModule($model): string
    {
        if ($model instanceof Sales && $model->type === Sales::TYPE_POS) {
            return ActivityLog::MODULE_POS;
        }
        return $this->module;
    }

    public function created($model): void
    {
        try {
            $module = $this->resolveModule($model);
            if (empty($module)) {
                return;
            }
            $label = $this->guessLabel($model);
            $this->logService->logCreate(
                $module,
                "Created {$module}" . ($label ? ": {$label}" : " #{$model->id}"),
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
            $module = $this->resolveModule($model);
            if (empty($module)) {
                return;
            }
            $label    = $this->guessLabel($model);
            $oldValues = $model->getOriginal();
            $newValues = $model->getChanges();

            if (empty($newValues)) {
                return; // nothing actually changed
            }

            $this->logService->logUpdate(
                $module,
                "Updated {$module}" . ($label ? ": {$label}" : " #{$model->id}"),
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
            $module = $this->resolveModule($model);
            if (empty($module)) {
                return;
            }
            $label = $this->guessLabel($model);
            $this->logService->logDelete(
                $module,
                "Deleted {$module}" . ($label ? ": {$label}" : " #{$model->id}"),
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
