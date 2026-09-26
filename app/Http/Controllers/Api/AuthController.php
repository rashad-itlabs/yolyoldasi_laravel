<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhoneVerificationCode;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\SmsSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class AuthController extends Controller
{
    /** Kodun etibarlılıq müddəti (saniyə). */
    private const CODE_TTL = 300;

    /** Yeni kod istəmək üçün gözləmə müddəti (saniyə). */
    private const RESEND_AFTER = 60;

    /** Bir kod üçün icazə verilən yanlış cəhd sayı. */
    private const MAX_ATTEMPTS = 5;

    /**
     * 1-ci addım: nömrə göndərilir, 6 rəqəmli kod yaranır və SMS ilə gedir.
     *
     * Kodun cavabda qaytarılması (`dev.otp_expose_code`) yalnız provayder
     * qoşulmayan mühitlər üçün qalıb və istismarda söndürülür — qalsa, nömrəni
     * bilən hər kəs cavabdan kodu oxuyub o hesaba girə bilər.
     */
    public function requestCode(Request $request, SmsSender $sms)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $this->normalizePhone($request->input('phone'));

        if (! preg_match('/^\+\d{9,15}$/', $phone)) {
            return response()->json([
                'message' => 'Telefon nömrəsi düzgün deyil.',
                'errors'  => ['phone' => ['Nömrəni ölkə kodu ilə yaz, məsələn +994501234567.']],
            ], 422);
        }

        $last = PhoneVerificationCode::where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($last && $last->created_at) {
            $passed = (int) $last->created_at->diffInSeconds(now());

            if ($passed < self::RESEND_AFTER) {
                $wait = self::RESEND_AFTER - $passed;

                return response()->json([
                    'message'     => "Yeni kod üçün {$wait} saniyə gözlə.",
                    'retry_after' => $wait,
                ], 429);
            }
        }

        // Köhnə istifadə edilməmiş kodlar qüvvədən düşür — həmişə yalnız sonuncu işləyir.
        PhoneVerificationCode::where('phone', $phone)->whereNull('consumed_at')->delete();

        $code = (string) random_int(100000, 999999);

        PhoneVerificationCode::create([
            'phone'      => $phone,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addSeconds(self::CODE_TTL),
            'ip'         => $request->ip(),
        ]);

        $delivered = $sms->sendCode($phone, $code);

        $payload = [
            'message'      => 'Təsdiq kodu göndərildi.',
            'phone'        => $phone,
            'expires_in'   => self::CODE_TTL,
            'resend_after' => self::RESEND_AFTER,
        ];

        // Provayder cavab vermədi. Kod bazada yaradılıb və hələ də etibarlıdır,
        // ona görə axın dayandırılmır — klient sadəcə "SMS gecikə bilər"
        // deməlidir. 500 qaytarmaq daha pis olardı: istifadəçi yenidən cəhd
        // edər, `RESEND_AFTER` onu 60 saniyə gözlədər və heç nə düzəlməz.
        if (! $delivered && $sms->configured()) {
            $payload['delivery_failed'] = true;
        }

        if (config('dev.otp_expose_code')) {
            // Yalnız SMS qoşulmayan mühitlərdə. `config/dev.php` bunu
            // istismarda avtomatik söndürür.
            $payload['code'] = $code;
        }

        return response()->json($payload);
    }

    /**
     * 2-ci addım: nömrə + kod göndərilir, Sanctum tokeni qaytarılır.
     * Nömrə bazada yoxdursa yeni hesab yaranır (`is_new_user: true`).
     */
    public function verifyCode(Request $request, ReferralService $referrals)
    {
        $data = $request->validate([
            'phone'         => ['required', 'string', 'max:20'],
            'code'          => ['required', 'digits:6'],
            'full_name'     => ['nullable', 'string', 'min:3', 'max:255'],
            'role'          => ['nullable', 'in:passenger,driver'],
            'city_id'       => ['nullable', 'integer', 'exists:cities,id'],
            'device'        => ['nullable', 'in:web,app'],
            'referral_code' => ['nullable', 'string', 'max:12'],
        ]);

        $phone = $this->normalizePhone($data['phone']);

        $record = PhoneVerificationCode::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record) {
            return response()->json([
                'message' => 'Kod tapılmadı və ya vaxtı keçib. Yeni kod istə.',
            ], 422);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->forceFill(['consumed_at' => now()])->save();

            return response()->json([
                'message' => 'Çox sayda yanlış cəhd. Yeni kod istə.',
            ], 429);
        }

        if (! Hash::check($data['code'], $record->code_hash)) {
            $record->increment('attempts');

            return response()->json([
                'message'       => 'Kod yanlışdır.',
                'attempts_left' => max(0, self::MAX_ATTEMPTS - $record->attempts),
            ], 422);
        }

        $record->forceFill(['consumed_at' => now()])->save();

        $isNew = ! User::withTrashed()->where('phone', $phone)->exists();

        $user = $this->resolveUser($phone, $data);

        // Dəvət kodu yalnız yeni hesaba bağlanır: mövcud istifadəçi sonradan
        // kod yazıb kiməsə mükafat qazandıra bilməməlidir.
        if ($isNew) {
            $referrals->attach($user, $data['referral_code'] ?? null);
        }

        // Tətbiq girişidir — `device` göndərilməsə də token müddətsiz olmalıdır.
        return $this->issueToken($user, $data['device'] ?? 'app', $isNew);
    }

    /**
     * Firebase ID token-ini yoxlayır və Sanctum tokeni qaytarır.
     * Həm qeydiyyat (veb — ad/rol göndərir), həm giriş (tətbiq — yalnız token)
     * bu endpoint-dən keçir: nömrə bazada varsa mövcud hesab qaytarılır.
     */
    public function firebase(Request $request, FirebaseAuth $firebase)
    {
        $data = $request->validate([
            'id_token'  => ['required', 'string'],
            'full_name' => ['nullable', 'string', 'min:3', 'max:255'],
            'role'      => ['nullable', 'in:passenger,driver'],
            'city_id'   => ['nullable', 'integer', 'exists:cities,id'],
            'device'    => ['nullable', 'in:web,app'],
        ]);

        try {
            $claims = $firebase->verifyIdToken($data['id_token'])->claims();
        } catch (\Throwable $e) {
            Log::warning('Firebase ID token doğrulanmadı', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Təsdiq tokeni etibarsızdır. Yenidən cəhd et.'], 401);
        }

        $phone = $claims->get('phone_number');

        if (! $phone) {
            return response()->json(['message' => 'Token telefon nömrəsi daşımır.'], 422);
        }

        $user = $this->resolveUser((string) $phone, $data, (string) $claims->get('sub'));

        return $this->issueToken($user, $data['device'] ?? 'web');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Çıxış edildi.']);
    }

    /**
     * Hesabın silinməsi. Nömrə unique olduğu üçün sadəcə softDelete etsək,
     * həmin nömrə əbədi bloklanır və istifadəçi bir daha qeydiyyatdan keçə bilmir —
     * ona görə nömrəni anonimləşdirib sonra silirik.
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->deviceTokens()->delete();

            $user->forceFill([
                'phone'        => 'deleted_'.$user->id,
                'firebase_uid' => null,
                'photo_url'    => null,
                'about'        => null,
            ])->save();

            $user->delete();
        });

        return response()->json(['message' => 'Hesabın silindi.']);
    }

    /**
     * Nömrəyə uyğun hesabı tapır, yoxdursa yaradır. Firebase və SMS kodu
     * axınlarının ikisi də buradan keçir ki, hesab quruluşu eyni olsun.
     */
    private function resolveUser(string $phone, array $data, ?string $firebaseUid = null): User
    {
        return DB::transaction(function () use ($phone, $data, $firebaseUid) {
            $user = User::withTrashed()->firstOrNew(['phone' => $phone]);

            if ($user->exists && $user->trashed()) {
                $user->restore();
            }

            if ($firebaseUid) {
                $user->firebase_uid = $firebaseUid;
            }

            $user->phone_verified_at = $user->phone_verified_at ?? now();

            // Yalnız göndərilən sahələri yeniləyirik — girişdə tətbiq bunları
            // göndərmir və mövcud profil üzərinə boş dəyər yazılmamalıdır.
            if (! empty($data['full_name'])) {
                $user->full_name = $data['full_name'];
            }
            if (! empty($data['city_id'])) {
                $user->city_id = $data['city_id'];
            }
            if (! empty($data['role'])) {
                $user->active_mode = $data['role'];
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
    }

    /**
     * Vebdə token yalnız qeydiyyatın 3-cü addımı üçün lazımdır və heç yerdə
     * saxlanılmır; tətbiqdə isə giriş sessiyasıdır, ona görə müddətsizdir.
     */
    private function issueToken(User $user, string $device, ?bool $isNew = null)
    {
        if ($user->is_blocked) {
            return response()->json([
                'message' => 'Hesabın bloklanıb. Dəstək ilə əlaqə saxla.',
            ], 403);
        }

        $expires = $device === 'web' ? now()->addHour() : null;

        $payload = [
            'token' => $user->createToken($device, ['*'], $expires)->plainTextToken,
            'user'  => [
                'id'                 => $user->id,
                'full_name'          => $user->full_name,
                'phone'              => $user->phone,
                'active_mode'        => $user->active_mode,
                // Yeni yaranmış modeldə bu sahə hələ bazadan oxunmayıb və null olur —
                // klient `false` gözləyir, ona görə tipi burada sabitləyirik.
                'has_driver_profile' => (bool) $user->has_driver_profile,
            ],
        ];

        if ($isNew !== null) {
            $payload['is_new_user'] = $isNew;
        }

        return response()->json($payload);
    }

    /**
     * İstənilən yazılışı E.164-ə gətirir.
     *
     * `phone` sütunu unikaldır: eyni nömrə fərqli formatda gəlsə, eyni adam
     * üçün ikinci hesab yaranardı.
     *
     * Ölkə məhdudiyyəti yoxdur — tətbiq ölkə seçimi ilə tam nömrə göndərir və
     * `+` ilə başlayan hər şey olduğu kimi qəbul edilir. Azərbaycana xas
     * qaydalar yalnız `+`-sız gələn nömrəyə tətbiq olunur (köhnə tətbiq
     * versiyaları belə göndərirdi).
     */
    private function normalizePhone(string $phone): string
    {
        $phone  = trim($phone);
        $digits = (string) preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return '';
        }

        // Açıq beynəlxalq forma — olduğu kimi qəbul edilir.
        //
        // Tətbiq artıq ölkə seçimi ilə tam E.164 göndərir, ona görə `+` varsa
        // burada təxmin etməyə yer qalmır. Bu yoxlama olmasa aşağıdakı
        // Azərbaycan qaydaları xarici nömrələri korlayırdı: doqquz rəqəmli
        // gürcü nömrəsi `+994…` olurdu, trunk sıfırı ilə yazılmış türk nömrəsi
        // isə `+0555…` — heç biri mövcud nömrə deyil.
        if (str_starts_with($phone, '+')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        /*
         | Buradan aşağısı `+`-sız gələn nömrə üçündür və Azərbaycanı nəzərdə
         | tutur. Köhnə tətbiq versiyaları üçün saxlanılır — onlar ölkə kodunu
         | göndərmir və həmişə yerli nömrə deməkdir.
         */
        if (str_starts_with($digits, '994')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+994'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '+994'.$digits;
        }

        return '+'.$digits;
    }
}
