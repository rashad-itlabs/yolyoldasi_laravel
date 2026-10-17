<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\DriverProfile;
use App\Models\VerificationDocument;
use App\Services\NotificationService;
use App\Services\ProService;
use App\Support\DriverVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function __construct(
        private NotificationService $notifications,
        private ProService $pro,
    ) {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status']);
        $status  = $filters['status'] ?? 'pending';

        // Hesab silinəndə (soft delete) sürücü profili qalır — bərpa olunanda
        // geri gəlsin deyə. Siyahıda isə görünməməlidir: `user` null olur və
        // sətrə klik 500 verirdi. `whereHas` SoftDeletes scope-una hörmət edir.
        $profiles = DriverProfile::query()
            ->whereHas('user')
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

        $counts = DriverProfile::whereHas('user')
            ->selectRaw('status, COUNT(*) c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('admin.drivers.index', compact('profiles', 'filters', 'status', 'counts'));
    }

    public function show(DriverProfile $driver)
    {
        $driver->load(['user.stats', 'user.city', 'vehicle', 'documents']);

        // Köhnə link və ya jurnaldan gələn keçid: hesab silinib. İstifadəçi
        // səhifəsi silinmiş hesabı göstərir və bərpa düyməsi oradadır.
        if (! $driver->user) {
            return redirect()->route('admin.users.show', $driver->user_id)
                ->withErrors(['driver' => 'Bu sürücünün hesabı silinib. Bərpa etsəniz, sürücü profili də geri qayıdacaq.']);
        }

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
     * PRO verir və ya uzadır — qalan müddətin üstünə gəlinir.
     *
     * Sənəd statusu yoxlanılmır: sürücünün özü sınağı yalnız təsdiqdən sonra
     * başlada bilər, admin isə istisna edə bilməlidir. Elanların boost-u
     * `ProService` daxilində dərhal yenilənir.
     */
    public function grantPro(DriverProfile $driver)
    {
        $before = $driver->isPro() ? $driver->pro_until : null;

        DB::transaction(function () use ($driver, $before) {
            $this->pro->grant($driver, ProService::GRANT_DAYS);

            AdminActivityLog::record('driver.pro.granted', $driver, [
                'user_id'   => $driver->user_id,
                'days'      => ProService::GRANT_DAYS,
                'from'      => $before?->toIso8601String(),
                'pro_until' => $driver->pro_until->toIso8601String(),
            ]);
        });

        return back()->with('success', 'PRO '.$driver->pro_until->format('d.m.Y').' tarixinədək aktivdir.');
    }

    /**
     * PRO-nu dərhal bitirir. PRO-nun verdiyi boost elanlardan çıxır, dəvət
     * mükafatı varsa qalır; sınaq yenidən açılmır (bax `ProService::revoke`).
     */
    public function revokePro(DriverProfile $driver)
    {
        abort_unless($driver->isPro(), 422, 'Bu sürücünün aktiv PRO-su yoxdur.');

        $before = $driver->pro_until;

        DB::transaction(function () use ($driver, $before) {
            $this->pro->revoke($driver);

            AdminActivityLog::record('driver.pro.revoked', $driver, [
                'user_id'   => $driver->user_id,
                'pro_until' => $before->toIso8601String(),
            ]);
        });

        return back()->with('success', 'PRO ləğv edildi.');
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
