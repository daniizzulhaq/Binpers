<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')->value()))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', 'like', '%\\'.$request->string('type')->value()))
            ->when($request->integer('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->latest('id')->paginate(25)->withQueryString();

        return view('hrd.audit-logs.index', [
            'logs' => $logs,
            'events' => AuditLog::select('event')->distinct()->orderBy('event')->pluck('event'),
        ]);
    }
}
