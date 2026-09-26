<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['q', 'admin_id', 'action', 'date_from', 'date_to']);

        $logs = AdminActivityLog::query()
            ->with('admin')
            ->when($filters['admin_id'] ?? null, fn ($query, $id) => $query->where('admin_id', $id))
            ->when($filters['action'] ?? null, fn ($query, $a) => $query->where('action', $a))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('action', 'like', "%{$q}%")
                    ->orWhere('subject_type', 'like', "%{$q}%")
                    ->orWhere('subject_id', $q)
                    ->orWhere('ip', 'like', "%{$q}%")
            ))
            ->when($filters['date_from'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn ($query, $d) => $query->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $admins  = Admin::orderBy('name')->get(['id', 'name']);
        $actions = AdminActivityLog::distinct()->orderBy('action')->pluck('action');

        return view('admin.logs.index', compact('logs', 'filters', 'admins', 'actions'));
    }
}
