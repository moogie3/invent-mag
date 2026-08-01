<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Scopes\TenantScope;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Write an activity log entry.
     *
     * @param  string  $module  One of ActivityLog::MODULE_* constants
     * @param  string  $action  One of ActivityLog::ACTION_* constants
     * @param  string  $description  Human-readable description of what happened
     * @param  array|null  $oldValues  Snapshot of the record before the action
     * @param  array|null  $newValues  Snapshot of the record after the action
     * @param  string|null  $entityType  Fully-qualified model class name
     * @param  int|null  $entityId  Primary key of the affected record
     * @param  string|null  $entityLabel  Human-readable name of the record (e.g. product name)
     */
    public function log(
        string $module,
        string $action,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $entityLabel = null
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'tenant_id' => app('currentTenant')?->id,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_role' => $user?->getRoleNames()->first() ?? null,
            'module' => $module,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_label' => $entityLabel,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Convenience shortcuts
    // -------------------------------------------------------------------------

    public function logCreate(string $module, string $description, $model = null, ?array $newValues = null): ActivityLog
    {
        return $this->log(
            $module,
            ActivityLog::ACTION_CREATE,
            $description,
            null,
            $newValues ?? ($model ? $model->toArray() : null),
            $model ? get_class($model) : null,
            $model?->id,
            $this->guessLabel($model)
        );
    }

    public function logUpdate(string $module, string $description, $model = null, ?array $oldValues = null, ?array $newValues = null): ActivityLog
    {
        return $this->log(
            $module,
            ActivityLog::ACTION_UPDATE,
            $description,
            $oldValues,
            $newValues ?? ($model ? $model->toArray() : null),
            $model ? get_class($model) : null,
            $model?->id,
            $this->guessLabel($model)
        );
    }

    public function logDelete(string $module, string $description, $model = null, ?array $oldValues = null): ActivityLog
    {
        return $this->log(
            $module,
            ActivityLog::ACTION_DELETE,
            $description,
            $oldValues ?? ($model ? $model->toArray() : null),
            null,
            $model ? get_class($model) : null,
            $model?->id,
            $this->guessLabel($model)
        );
    }

    public function logExport(string $module, string $description): ActivityLog
    {
        return $this->log($module, ActivityLog::ACTION_EXPORT, $description);
    }

    public function logPayment(string $module, string $description, $model = null): ActivityLog
    {
        return $this->log(
            $module,
            ActivityLog::ACTION_PAYMENT,
            $description,
            null,
            $model ? $model->toArray() : null,
            $model ? get_class($model) : null,
            $model?->id,
            $this->guessLabel($model)
        );
    }

    // -------------------------------------------------------------------------
    // Query helpers (used by AuditLogController)
    // -------------------------------------------------------------------------

    /**
     * Return a paginated, filtered query of activity logs for the current tenant.
     */
    public function getPaginatedLogs(array $filters = [], int $perPage = 25)
    {
        $tenant = app('currentTenant');

        // Return empty paginator if no tenant
        if (! $tenant) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        $query = ActivityLog::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('entity_label', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Return all distinct module values (for filter dropdown).
     */
    public function getModules(): array
    {
        $tenant = app('currentTenant');

        if (! $tenant) {
            return [];
        }

        return DB::table('activity_logs')
            ->where('tenant_id', $tenant->id)
            ->selectRaw('DISTINCT module')
            ->orderBy('module')
            ->pluck('module')
            ->toArray();
    }

    /**
     * Return all distinct action values (for filter dropdown).
     */
    public function getActions(): array
    {
        $tenant = app('currentTenant');

        if (! $tenant) {
            return [];
        }

        return DB::table('activity_logs')
            ->where('tenant_id', $tenant->id)
            ->selectRaw('DISTINCT action')
            ->orderBy('action')
            ->pluck('action')
            ->toArray();
    }

    /**
     * Return users who have activity log entries (for filter dropdown).
     */
    public function getActiveUsers(): Collection
    {
        $tenant = app('currentTenant');

        if (! $tenant) {
            return collect([]);
        }

        return DB::table('activity_logs')
            ->where('tenant_id', $tenant->id)
            ->selectRaw('DISTINCT user_id, user_name, user_role')
            ->whereNotNull('user_id')
            ->orderBy('user_name')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Try to extract a human-readable label from a model instance.
     * Checks common name columns in order of priority.
     */
    protected function guessLabel($model): ?string
    {
        if (! $model) {
            return null;
        }

        foreach (['name', 'title', 'invoice', 'code', 'email'] as $col) {
            if (isset($model->{$col}) && ! empty($model->{$col})) {
                return (string) $model->{$col};
            }
        }

        return null;
    }
}
