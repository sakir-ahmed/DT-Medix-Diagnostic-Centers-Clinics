<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientAccessLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = PatientAccessLog::query()
            ->with('user:id,name')
            ->when($request->query('patient'), function (Builder $q, $code): void {
                $q->whereIn('patient_id', Patient::query()->where('patient_code', strtoupper((string) $code))->select('id'));
            })
            ->when($request->query('action'), fn (Builder $q, $action) => $q->where('action', $action))
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 50))
            ->withQueryString();

        $patients = Patient::query()->withTrashed()->whereIn('id', $logs->pluck('patient_id'))->pluck('patient_code', 'id');

        return Inertia::render('Admin/AccessLogs/Index', [
            'logs' => $logs->through(fn (PatientAccessLog $log): array => [
                'id' => $log->id,
                'created_at' => $log->created_at->toIso8601String(),
                'user' => $log->user?->name,
                'patient_code' => $patients[$log->patient_id] ?? null,
                'action' => $log->action,
                'channel' => $log->channel,
                'ip_address' => $log->ip_address,
            ]),
            'filters' => ['patient' => $request->query('patient'), 'action' => $request->query('action')],
        ]);
    }
}
