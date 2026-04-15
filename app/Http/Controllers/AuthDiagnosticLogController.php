<?php

namespace App\Http\Controllers;

use App\Models\AuthDiagnosticLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthDiagnosticLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'event'  => $request->input('event', ''),
            'level'  => $request->input('level', ''),
            'user_id' => $request->input('user_id', ''),
        ];

        $query = AuthDiagnosticLog::query()->orderByDesc('created_at');

        if ($filters['event'] !== '') {
            $query->where('event', 'like', $filters['event'].'%');
        }

        if ($filters['level'] !== '') {
            $query->where('level', $filters['level']);
        }

        if ($filters['user_id'] !== '') {
            $query->where('user_id', (int) $filters['user_id']);
        }

        $logs = $query->paginate(50)->withQueryString();

        $keyFingerprint = substr(hash('sha256', (string) config('app.key')), 0, 16);
        $eventPrefixes = AuthDiagnosticLog::query()
            ->selectRaw("substr(event, 1, instr(event || '.', '.') - 1) as prefix")
            ->distinct()
            ->orderBy('prefix')
            ->pluck('prefix')
            ->unique()
            ->values();

        return view('auth-diagnostics.index', compact('logs', 'filters', 'keyFingerprint', 'eventPrefixes'));
    }

    public function show(AuthDiagnosticLog $authDiagnosticLog): View
    {
        $keyFingerprint = substr(hash('sha256', (string) config('app.key')), 0, 16);

        return view('auth-diagnostics.show', [
            'log'            => $authDiagnosticLog,
            'keyFingerprint' => $keyFingerprint,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:DELETE'],
        ]);

        AuthDiagnosticLog::truncate();

        return redirect()->route('auth-diagnostics.index')
            ->with('status', 'All auth diagnostic logs cleared.');
    }
}

