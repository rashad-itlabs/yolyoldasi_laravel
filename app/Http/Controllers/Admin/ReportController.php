<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public const REASON_LABELS = [
        'no_show'        => 'Gəlmədi',
        'unsafe_driving' => 'Təhlükəli sürmə',
        'rude_behaviour' => 'Kobud davranış',
        'wrong_vehicle'  => 'Fərqli avtomobil',
        'price_dispute'  => 'Qiymət mübahisəsi',
        'spam'           => 'Spam',
        'other'          => 'Digər',
    ];

    public const STATUS_LABELS = [
        'open'      => 'Açıq',
        'resolved'  => 'Həll edilib',
        'dismissed' => 'Rədd edilib',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status', 'reason']);
        $status  = $filters['status'] ?? 'open';

        $reports = Report::query()
            ->with(['reporter', 'targetUser'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($filters['reason'] ?? null, fn ($query, $r) => $query->where('reason', $r))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(
                fn ($inner) => $inner->where('id', $q)
                    ->orWhereHas('targetUser', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
                    ->orWhereHas('reporter', fn ($u) => $u->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = Report::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.reports.index', compact('reports', 'filters', 'status', 'counts'));
    }

    public function show(Report $report)
    {
        $report->load(['reporter.stats', 'targetUser.stats']);

        // Hədəf haqqında başqa şikayətlər qərar üçün vacibdir.
        $otherReports = Report::with('reporter')
            ->where('target_user_id', $report->target_user_id)
            ->where('id', '!=', $report->id)
            ->latest()
            ->get();

        return view('admin.reports.show', compact('report', 'otherReports'));
    }

    public function resolve(Request $request, Report $report)
    {
        $this->decide($request, $report, 'resolved');

        return redirect()->route('admin.reports.show', $report)
            ->with('success', 'Şikayət həll edildi.');
    }

    public function dismiss(Request $request, Report $report)
    {
        $this->decide($request, $report, 'dismissed');

        return redirect()->route('admin.reports.show', $report)
            ->with('success', 'Şikayət rədd edildi.');
    }

    private function decide(Request $request, Report $report, string $status): void
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($report->status === 'open', 422, 'Bu şikayət artıq cavablandırılıb.');

        DB::transaction(function () use ($report, $status, $data) {
            $report->update([
                'status'      => $status,
                'admin_note'  => $data['admin_note'] ?? null,
                'resolved_at' => now(),
                'resolved_by' => Auth::guard('admin')->id(),
            ]);

            AdminActivityLog::record("report.{$status}", $report, [
                'target_user_id' => $report->target_user_id,
                'note'           => $data['admin_note'] ?? null,
            ]);
        });
    }

    /** Açıq şikayəti yenidən açır — səhv qərar geri qaytarıla bilsin. */
    public function reopen(Report $report)
    {
        abort_if($report->status === 'open', 422, 'Bu şikayət onsuz da açıqdır.');

        DB::transaction(function () use ($report) {
            $report->update([
                'status'      => 'open',
                'resolved_at' => null,
                'resolved_by' => null,
            ]);

            AdminActivityLog::record('report.reopened', $report);
        });

        return back()->with('success', 'Şikayət yenidən açıldı.');
    }
}
