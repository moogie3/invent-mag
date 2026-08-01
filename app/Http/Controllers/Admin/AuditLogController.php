<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Scopes\TenantScope;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected ActivityLogService $logService;

    public function __construct(ActivityLogService $logService)
    {
        // Only superusers may access the audit log (bypasses all plan limits)
        // Role check is sufficient since superuser gets all permissions automatically
        $this->middleware(['role:superuser']);
        $this->logService = $logService;
    }

    /**
     * Display the paginated, filterable audit log.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'module', 'action', 'search', 'date_from', 'date_to']);

        $tenant = app('currentTenant');
        $perPage = $request->input('entries', 25);
        $logs = $this->logService->getPaginatedLogs($filters, $perPage);

        // Filter options
        $modules = $this->logService->getModules();
        $actions = $this->logService->getActions();
        $users = $this->logService->getActiveUsers();

        // Summary stats (for the top cards) - filter by current tenant
        $tenantId = $tenant?->id;
        $today = $tenantId ? ActivityLog::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->whereDate('created_at', today())->count() : 0;
        $thisWeek = $tenantId ? ActivityLog::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->where('created_at', '>=', now()->startOfWeek())->count() : 0;
        $thisMonth = $tenantId ? ActivityLog::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->where('created_at', '>=', now()->startOfMonth())->count() : 0;
        $totalEvents = $tenantId ? ActivityLog::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenantId)->count() : 0;

        return view('admin.audit-log.index', compact(
            'logs',
            'filters',
            'modules',
            'actions',
            'users',
            'perPage',
            'today',
            'thisWeek',
            'thisMonth',
            'totalEvents'
        ));
    }

    /**
     * Show the detail/diff for a single log entry (AJAX modal).
     */
    public function show(ActivityLog $auditLog)
    {
        return response()->json([
            'id' => $auditLog->id,
            'user_name' => $auditLog->user_name,
            'user_role' => $auditLog->user_role,
            'module' => $auditLog->module,
            'action' => $auditLog->action,
            'entity_label' => $auditLog->entity_label,
            'description' => $auditLog->description,
            'old_values' => $auditLog->old_values,
            'new_values' => $auditLog->new_values,
            'ip_address' => $auditLog->ip_address,
            'user_agent' => $auditLog->user_agent,
            'created_at' => $auditLog->created_at->translatedFormat('d M Y, H:i:s'),
        ]);
    }
}
