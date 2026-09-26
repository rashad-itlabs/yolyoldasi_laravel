<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SMS-siz token almaq üçün müvəqqəti endpoint — Postman və Flutter
 * inkişafı zamanı Firebase axınını gözləməmək üçün.
 *
 * DİQQƏT: bu endpoint telefon nömrəsini YOXLAMIR. Aktiv olduğu müddətdə
 * açarı bilən hər kəs istənilən hesabın tokenini ala bilər.
 * İstismara verməzdən əvvəl DEV_AUTH_ENABLED=false et, ən yaxşısı isə
 * bu faylı, config/dev.php-ı və route sətrini tamamilə sil.
 */
class DevAuthController extends Controller
{
    public function login(Request $request)
    {
        $this->ensureEnabled($request);

        $data = $request->validate([
            'phone'     => ['required', 'string', 'max:20'],
            'full_name' => ['nullable', 'string', 'min:3', 'max:255'],
            'role'      => ['nullable', 'in:passenger,driver'],
            'city_id'   => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::withTrashed()->firstOrNew(['phone' => $data['phone']]);

            if ($user->exists && $user->trashed()) {
                $user->restore();
            }

            $user->full_name         = $data['full_name'] ?? ($user->full_name ?: 'Test İstifadəçi');
            $user->phone_verified_at = $user->phone_verified_at ?? now();

            if (! empty($data['role'])) {
                $user->active_mode = $data['role'];
            }
            if (! empty($data['city_id'])) {
                $user->city_id = $data['city_id'];
            }

            $user->save();

            $user->stats()->firstOrCreate([]);
            $user->notificationPreference()->firstOrCreate([]);

            if (($data['role'] ?? null) === 'driver') {
                $user->driverProfile()->firstOrCreate([]);
                $user->forceFill(['has_driver_profile' => true])->save();
            }

            return $user;
        });

        Log::warning('DEV auth tokeni verildi', [
            'phone' => $user->phone,
            'ip'    => $request->ip(),
        ]);

        return response()->json([
            'token' => $user->createToken('dev', ['*'], now()->addDay())->plainTextToken,
            'user'  => [
                'id'                 => $user->id,
                'full_name'          => $user->full_name,
                'phone'              => $user->phone,
                'active_mode'        => $user->active_mode,
                'has_driver_profile' => $user->has_driver_profile,
            ],
        ]);
    }

    /**
     * MÜVƏQQƏTİ diaqnostika: serverə hansı Authorization başlığının çatdığını
     * göstərir. Token sızmasın deyə yalnız uzunluq və ilk/son simvollar verilir.
     */
    public function headers(Request $request)
    {
        abort_unless(config('dev.auth_enabled'), 404);

        $auth = (string) $request->header('Authorization', '');

        return response()->json([
            'authorization_gəldi'   => $auth !== '',
            'uzunluq'               => strlen($auth),
            'ilk_15_simvol'         => substr($auth, 0, 15),
            'son_6_simvol'          => $auth === '' ? '' : substr($auth, -6),
            'bearer_ile_baslayir'   => str_starts_with($auth, 'Bearer '),
            'bearer_token_oxundu'   => $request->bearerToken() !== null,
            'user_agent'            => $request->userAgent(),
            'butun_basliqlar'       => array_keys($request->headers->all()),
        ]);
    }

    /**
     * Söndürülü və ya açar yanlış olduqda 404 qaytarırıq (403 yox) —
     * endpoint-in mövcudluğu kənardan bilinməməlidir.
     */
    private function ensureEnabled(Request $request): void
    {
        abort_unless(config('dev.auth_enabled'), 404);

        $secret = (string) config('dev.auth_secret');

        abort_if($secret === '', 404);
        abort_unless(
            hash_equals($secret, (string) $request->header('X-Dev-Secret')),
            404
        );
    }
}
