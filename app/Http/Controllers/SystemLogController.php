<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureUiEnabled();

        $filters = [
            'channel' => trim((string) $request->input('channel', '')),
            'level' => trim((string) $request->input('level', '')),
            'q' => trim((string) $request->input('q', '')),
            'from' => trim((string) $request->input('from', '')),
            'to' => trim((string) $request->input('to', '')),
            'per_page' => max(10, min(100, (int) $request->input('per_page', 50))),
        ];

        $query = SystemLog::query()->orderByDesc('logged_at');

        if ($filters['channel'] !== '') {
            $query->channel($filters['channel']);
        }

        if ($filters['level'] !== '') {
            $query->level($filters['level']);
        }

        if ($filters['q'] !== '') {
            $query->where('message', 'like', '%'.$filters['q'].'%');
        }

        if ($filters['from'] !== '') {
            $query->whereDate('logged_at', '>=', $filters['from']);
        }

        if ($filters['to'] !== '') {
            $query->whereDate('logged_at', '<=', $filters['to']);
        }

        $logs = $query->paginate($filters['per_page'])->withQueryString();

        $channels = SystemLog::query()
            ->whereNotNull('channel')
            ->select('channel')
            ->distinct()
            ->orderBy('channel')
            ->pluck('channel')
            ->values();

        $levels = SystemLog::query()
            ->select('level')
            ->distinct()
            ->orderBy('level')
            ->pluck('level')
            ->values();

        return view('system-logs.index', compact('logs', 'filters', 'channels', 'levels'));
    }

    public function show(SystemLog $systemLog): View
    {
        $this->ensureUiEnabled();

        return view('system-logs.show', [
            'log' => $systemLog,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->ensureUiEnabled();

        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ]);

        SystemLog::truncate();

        return redirect()->route('system-logs.index')
            ->with('status', 'All system logs cleared.');
    }

    private function ensureUiEnabled(): void
    {
        abort_unless(Gate::allows('view-admin-tools'), 403);
        abort_unless(config('app.system_logs_ui_enabled', false), 404);
    }
}

