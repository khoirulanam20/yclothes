<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ModelSerializer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->action.'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20)->withQueryString();
        $users = User::where(function ($q) {
            $q->where('is_admin', true)->orWhereNotNull('admin_role_id');
        })->orderBy('name')->get();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => ModelSerializer::paginated($logs, [ModelSerializer::class, 'activityLog']),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()->all(),
            'filters' => $request->only(['user_id', 'action', 'date_from', 'date_to']),
        ]);
    }
}
