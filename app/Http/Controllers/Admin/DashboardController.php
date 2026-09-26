<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Booking;
use App\Models\DriverProfile;
use App\Models\Report;
use App\Models\Ride;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users_total'      => User::count(),
            'users_new_7d'     => User::where('created_at', '>=', now()->subDays(7))->count(),
            'users_blocked'    => User::where('is_blocked', true)->count(),
            'drivers_pending'  => DriverProfile::where('status', 'pending')->count(),
            'drivers_approved' => DriverProfile::where('status', 'approved')->count(),
            'rides_active'     => Ride::where('status', 'active')->count(),
            'rides_total'      => Ride::count(),
            'bookings_pending' => Booking::where('status', 'pending')->count(),
            'bookings_total'   => Booking::count(),
            'reports_open'     => Report::where('status', 'open')->count(),
        ];

        // Son 14 günün qeydiyyat və səfər sayı — qrafik üçün.
        $chart = $this->dailySeries(14);

        $recentReports = Report::with(['reporter', 'targetUser'])
            ->where('status', 'open')
            ->latest()
            ->limit(5)
            ->get();

        $pendingDrivers = DriverProfile::with('user')
            ->where('status', 'pending')
            ->orderBy('submitted_at')
            ->limit(5)
            ->get();

        $recentActivity = AdminActivityLog::with('admin')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'stats', 'chart', 'recentReports', 'pendingDrivers', 'recentActivity'
        ));
    }

    /**
     * Gün-gün say. Boş günlər sıfırla doldurulur, yoxsa qrafikin oxu
     * sıçrayır və iki sıra bir-birinə uyğun gəlmir.
     */
    private function dailySeries(int $days): array
    {
        $from = now()->startOfDay()->subDays($days - 1);

        $users = User::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) d, COUNT(*) c')
            ->groupBy('d')->pluck('c', 'd');

        $rides = Ride::where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) d, COUNT(*) c')
            ->groupBy('d')->pluck('c', 'd');

        $labels = [];
        $userSeries = [];
        $rideSeries = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i);
            $key = $day->toDateString();

            $labels[]     = $day->format('d.m');
            $userSeries[] = (int) ($users[$key] ?? 0);
            $rideSeries[] = (int) ($rides[$key] ?? 0);
        }

        return ['labels' => $labels, 'users' => $userSeries, 'rides' => $rideSeries];
    }
}
