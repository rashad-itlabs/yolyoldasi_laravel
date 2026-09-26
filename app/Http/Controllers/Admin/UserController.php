<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Booking;
use App\Models\City;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Token müddəti üçün hazır variantlar — açar gün sayı və ya `never`. */
    public const TOKEN_LIFETIMES = [
        '1'     => '1 gün',
        '7'     => '7 gün',
        '30'    => '30 gün',
        '365'   => '1 il',
        'never' => 'Müddətsiz',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['q', 'status', 'mode', 'city_id']);

        $users = User::query()
            ->with('city')
            ->withCount(['rides', 'bookings'])
            ->when($filters['q'] ?? null, function ($query, $q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('id', $q);
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => match ($status) {
                'blocked'  => $query->where('is_blocked', true),
                'active'   => $query->where('is_blocked', false),
                'driver'   => $query->where('has_driver_profile', true),
                'deleted'  => $query->onlyTrashed(),
                default    => $query,
            })
            ->when($filters['mode'] ?? null, fn ($query, $mode) => $query->where('active_mode', $mode))
            ->when($filters['city_id'] ?? null, fn ($query, $id) => $query->where('city_id', $id))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $cities = City::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'filters', 'cities'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    /**
     * Panel üzərindən hesab açmaq — test və dəstək halları üçün. Tətbiqdəki
     * axından fərqli olaraq Firebase yoxlanışı yoxdur, ona görə nömrə
     * təsdiqini admin özü seçir.
     */
    public function store(Request $request)
    {
        $request->merge(['phone' => self::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'phone'         => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/', Rule::unique('users', 'phone')],
            'full_name'     => ['required', 'string', 'min:3', 'max:255'],
            'city_id'       => ['nullable', 'integer', 'exists:cities,id'],
            'gender'        => ['required', 'in:male,female,unspecified'],
            'birth_year'    => ['nullable', 'integer', 'min:1930', 'max:'.(date('Y') - 16)],
            'active_mode'   => ['required', 'in:passenger,driver'],
            'language_code' => ['required', 'in:az,en,ru'],
            'verified'      => ['nullable', 'boolean'],
        ], [
            'phone.regex'  => 'Nömrə beynəlxalq formatda olmalıdır: +994501234567.',
            'phone.unique' => 'Bu nömrə artıq mövcuddur — silinmiş hesab da ola bilər (süzgəcdə «Silinmiş»).',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'phone'             => $data['phone'],
                'full_name'         => $data['full_name'],
                'city_id'           => $data['city_id'] ?? null,
                'gender'            => $data['gender'],
                'birth_year'        => $data['birth_year'] ?? null,
                'active_mode'       => $data['active_mode'],
                'language_code'     => $data['language_code'],
                'phone_verified_at' => ($data['verified'] ?? false) ? now() : null,
            ]);

            $user->stats()->create([]);
            $user->notificationPreference()->create([]);

            // Sərnişin rejimindən sürücüyə keçid `has_driver_profile` tələb edir
            // (Api\ProfileController::updateMode), ona görə profil elə indi açılır.
            if ($data['active_mode'] === 'driver') {
                $user->driverProfile()->create([]);
                $user->forceFill(['has_driver_profile' => true])->save();
            }

            AdminActivityLog::record('user.created', $user, [
                'phone' => $user->phone,
                'mode'  => $user->active_mode,
            ]);

            return $user;
        });

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', "İstifadəçi #{$user->id} yaradıldı.");
    }

    public function show(int $id)
    {
        $user = User::withTrashed()
            ->with(['city', 'stats', 'vehicles', 'driverProfile.documents'])
            ->findOrFail($id);

        $rides = $user->rides()->with(['fromCity', 'toCity'])->latest('departure_at')->limit(10)->get();

        $bookings = Booking::with(['ride.fromCity', 'ride.toCity'])
            ->where('passenger_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $reviews = Review::with('author')
            ->where('target_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $reportsAgainst = Report::with('reporter')
            ->where('target_user_id', $user->id)
            ->latest()
            ->get();

        // Sanctum tokeni yalnız hash kimi saxlayır — siyahıda metadata görünür,
        // açıq dəyər isə ancaq yaradıldığı anda bir dəfə göstərilir.
        $tokens = $user->tokens()->latest('id')->get();

        return view('admin.users.show', compact(
            'user', 'rides', 'bookings', 'reviews', 'reportsAgainst', 'tokens'
        ));
    }

    /**
     * İstifadəçi adından API tokeni verir. Açıq mətn geri qaytarılmır, ona görə
     * bir dəfəlik flash ilə ötürülür — səhifə yenilənəndə itir.
     */
    public function storeToken(Request $request, User $user)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:60'],
            'expires' => ['required', Rule::in(array_keys(self::TOKEN_LIFETIMES))],
        ]);

        // Bloklu hesabın tokeni işlək qalır — blok yalnız girişdə yoxlanılır
        // (Api\AuthController::firebase), ona görə token burada verilmir.
        if ($user->is_blocked) {
            return back()->withErrors(['token' => 'Bloklu hesab üçün token vermək olmaz — əvvəlcə blokdan çıxarın.']);
        }

        $expiresAt = $data['expires'] === 'never' ? null : now()->addDays((int) $data['expires']);

        $plainTextToken = DB::transaction(function () use ($user, $data, $expiresAt) {
            $token = $user->createToken($data['name'], ['*'], $expiresAt);

            AdminActivityLog::record('user.token.created', $user, [
                'token_id'   => $token->accessToken->getKey(),
                'token_name' => $data['name'],
                'expires_at' => $expiresAt?->toDateTimeString() ?? 'müddətsiz',
            ]);

            return $token->plainTextToken;
        });

        return back()
            ->with('success', 'Token yaradıldı — açıq dəyəri yalnız indi görünür.')
            ->with('new_token', $plainTextToken);
    }

    public function destroyToken(User $user, int $token)
    {
        $accessToken = $user->tokens()->whereKey($token)->firstOrFail();

        DB::transaction(function () use ($user, $accessToken) {
            AdminActivityLog::record('user.token.revoked', $user, [
                'token_id'   => $accessToken->getKey(),
                'token_name' => $accessToken->name,
            ]);

            $accessToken->delete();
        });

        return back()->with('success', "Token #{$accessToken->getKey()} ləğv edildi.");
    }

    public function block(Request $request, User $user)
    {
        $data = $request->validate([
            'block_reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->forceFill([
                'is_blocked'   => true,
                'block_reason' => $data['block_reason'],
                'blocked_at'   => now(),
            ])->save();

            AdminActivityLog::record('user.blocked', $user, ['reason' => $data['block_reason']]);
        });

        return back()->with('success', "İstifadəçi #{$user->id} bloklandı.");
    }

    public function unblock(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->forceFill([
                'is_blocked'   => false,
                'block_reason' => null,
                'blocked_at'   => null,
            ])->save();

            AdminActivityLog::record('user.unblocked', $user);
        });

        return back()->with('success', "İstifadəçi #{$user->id} blokdan çıxarıldı.");
    }

    /** Yumşaq silmə — hesab bərpa oluna bilsin deyə sətir qalır. */
    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            AdminActivityLog::record('user.deleted', $user, ['phone' => $user->phone]);
            $user->delete();
        });

        return redirect()->route('admin.users.index')
            ->with('success', "İstifadəçi #{$user->id} silindi.");
    }

    public function restore(int $id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        DB::transaction(function () use ($user) {
            $user->restore();
            AdminActivityLog::record('user.restored', $user);
        });

        return back()->with('success', "İstifadəçi #{$user->id} bərpa edildi.");
    }

    /**
     * `050 123 45 67`, `0501234567`, `994501234567` → `+994501234567`.
     * Nömrə bazada E.164 saxlanılır; forma boşluqlu yazılışı da qəbul etməlidir.
     */
    private static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return trim($phone);
        }

        // Yerli yazılış: 0 ilə başlayan 10 rəqəm → ölkə kodu əlavə olunur.
        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '994'.substr($digits, 1);
        }

        return '+'.$digits;
    }
}
