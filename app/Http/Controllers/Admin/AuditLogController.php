<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:100'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $logs = $this->query($filters)->paginate(30)->withQueryString();

        return view('admin.audit.index', compact('logs', 'filters'));
    }

    public function export(Request $request): Response
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:100'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        return response()->streamDownload(function () use ($filters): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Waktu', 'Pengguna', 'Aksi', 'Target', 'IP']);
            $this->query($filters)->chunk(500, function ($logs) use ($output): void {
                foreach ($logs as $log) {
                    fputcsv($output, [$log->created_at, $log->user?->email, $log->action, $log->target_type.'#'.$log->target_id, $log->ip_address]);
                }
            });
            fclose($output);
        }, 'audit-log.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @param array<string, mixed> $filters */
    private function query(array $filters)
    {
        return AuditLog::query()->with('user:id,name,email')
            ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', $action.'%'))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('id');
    }
}
