<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\DriverProfile;
use App\Models\VerificationDocument;
use App\Services\NotificationService;
use App\Support\DriverVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status']);
        $status  = $filters['status'] ?? 'pending';

        $profiles = DriverProfile::query()
            ->with(['user', 'vehicle'])
            ->withCount('documents')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->whereHas(
                'user',
                fn ($u) => $u->where('full_name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")
            ))
            ->orderByRaw("FIELD(status,'pending','rejected','approved','not_uploaded')")
            ->orderBy('submitted_at')
            ->paginate(25)
            ->withQueryString();

        $counts = DriverProfile::selectRaw('status, COUNT(*) c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('admin.drivers.index', compact('profiles', 'filters', 'status', 'counts'));
    }

    public function show(DriverProfile $driver)
    {
        $driver->load(['user.stats', 'user.city', 'vehicle', 'documents']);

        // Şəxsiyyət sənədlərinə baxış izlənilir. Qeyd səhifə açılanda bir dəfə
        // yazılır — hər şəkil sorğusunda yazsaydıq, jurnal önizləmələrlə dolardı.
        AdminActivityLog::record('driver.documents.viewed', $driver, [
            'user_id' => $driver->user_id,
        ]);

        // Yüklənməmiş növlər də sətir kimi görünsün ki, nəyin çatmadığı bilinsin.
        $existing  = $driver->documents->keyBy('type');
        $documents = collect(VerificationDocument::TYPES)->map(fn ($type) => [
            'type'      => $type,
            'label'     => self::DOCUMENT_LABELS[$type],
            'model'     => $existing->get($type),
            'two_sided' => in_array($type, VerificationDocument::TWO_SIDED, true),
        ]);

        return view('admin.drivers.show', compact('driver', 'documents'));
    }

    public const DOCUMENT_LABELS = [
        'id_card'              => 'Şəxsiyyət vəsiqəsi',
        'driver_license'       => 'Sürücülük vəsiqəsi',
        'vehicle_registration' => 'Avtomobil qeydiyyatı',
        'insurance'            => 'Sığorta',
    ];

    public function approveDocument(VerificationDocument $document)
    {
        $this->decideDocument($document, 'approved', null);

        return back()->with('success', 'Sənəd təsdiqləndi.');
    }

    public function rejectDocument(Request $request, VerificationDocument $document)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $this->decideDocument($document, 'rejected', $data['rejection_reason']);

        return back()->with('success', 'Sənəd rədd edildi.');
    }

    /**
     * Sənəd qərarı profilin ümumi statusunu dəyişir, ona görə hər ikisi bir
     * tranzaksiyada yazılır.
     */
    private function decideDocument(VerificationDocument $document, string $status, ?string $reason): void
    {
        DB::transaction(function () use ($document, $status, $reason) {
            $document->forceFill([
                'status'           => $status,
                'rejection_reason' => $reason,
                'reviewed_at'      => now(),
            ])->save();

            $profile = $document->driverProfile()->firstOrFail();

            $newStatus = DriverVerification::refresh($profile);

            $profile->forceFill([
                'reviewed_at' => now(),
                'reviewed_by' => Auth::guard('admin')->id(),
            ])->save();

            // Sürücü yalnız ümumi status dəyişəndə xəbərdar edilir — hər
            // sənəd üçün ayrıca bildiriş göndərmək lazımsız səs-küy olardı.
            if (in_array($newStatus, ['approved', 'rejected'], true)) {
                $this->notifications->send(
                    $profile->user_id,
                    $newStatus === 'approved' ? 'documentsApproved' : 'documentsRejected',
                    ['payload' => ['reason' => $reason]]
                );
            }

            AdminActivityLog::record("driver.document.{$status}", $document, [
                'driver_profile_id' => $profile->id,
                'user_id'           => $profile->user_id,
                'type'              => $document->type,
                'reason'            => $reason,
                'profile_status'    => $newStatus,
            ]);
        });
    }

    /** Bütün sənədləri bir hərəkətlə təsdiqləyir. */
    public function approveAll(DriverProfile $driver)
    {
        DB::transaction(function () use ($driver) {
            VerificationDocument::where('driver_profile_id', $driver->id)
                ->whereNotNull('file_url')
                ->update(['status' => 'approved', 'rejection_reason' => null, 'reviewed_at' => now()]);

            $newStatus = DriverVerification::refresh($driver);

            $driver->forceFill([
                'reviewed_at'      => now(),
                'reviewed_by'      => Auth::guard('admin')->id(),
                'rejection_reason' => null,
            ])->save();

            $this->notifications->send($driver->user_id, 'documentsApproved');

            AdminActivityLog::record('driver.approved', $driver, [
                'user_id'        => $driver->user_id,
                'profile_status' => $newStatus,
            ]);
        });

        return back()->with('success', 'Sürücü təsdiqləndi.');
    }

    /** Profili bütövlükdə rədd edir — sənədlərə toxunmur, səbəb profilə yazılır. */
    public function reject(Request $request, DriverProfile $driver)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($driver, $data) {
            $driver->forceFill([
                'status'           => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'reviewed_at'      => now(),
                'reviewed_by'      => Auth::guard('admin')->id(),
            ])->save();

            $this->notifications->send($driver->user_id, 'documentsRejected', [
                'payload' => ['reason' => $data['rejection_reason']],
            ]);

            AdminActivityLog::record('driver.rejected', $driver, [
                'user_id' => $driver->user_id,
                'reason'  => $data['rejection_reason'],
            ]);
        });

        return back()->with('success', 'Sürücü rədd edildi.');
    }

    /**
     * Sənəd faylları `local` diskdədir (storage/app/private) — veb üzərindən
     * birbaşa açıq deyil, ona görə yalnız bu yoldan, admin sessiyası ilə verilir.
     */
    public function document(VerificationDocument $document, string $side = 'front')
    {
        abort_unless(in_array($side, ['front', 'back'], true), 404);

        $path = $side === 'back' ? $document->back_file_url : $document->file_url;

        abort_if(! $path || ! Storage::disk('local')->exists($path), 404, 'Fayl tapılmadı.');

        return Storage::disk('local')->response($path);
    }
}
