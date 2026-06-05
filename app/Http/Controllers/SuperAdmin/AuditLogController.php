<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::latest();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(50);

        $modules = ActivityLog::select('module')->distinct()->pluck('module');
        $actions = [
            ActivityLog::ACTION_CREATE,
            ActivityLog::ACTION_UPDATE,
            ActivityLog::ACTION_DELETE,
            ActivityLog::ACTION_LOGIN,
            ActivityLog::ACTION_LOGOUT,
        ];

        return view('superadmin.audit-log.index', compact('logs', 'modules', 'actions'));
    }

    public function show(ActivityLog $log)
    {
        return view('superadmin.audit-log.show', compact('log'));
    }
}
