<?php

namespace App\Http\Controllers\Web;

use App\Mail\EmailCodeMail;
use App\Models\EmailCode;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Saytın girişi — e-poçt + şifrə, qeydiyyat e-poçta gedən kodla.
 *
 * Sayt hesabları hələlik tətbiq hesablarından ayrıdır: tətbiq telefon və SMS
 * kodu ilə girir, burada telefon soruşulmur. Belə olmasa, tətbiqdə artıq olan
 * nömrəni yazan adam başqasının hesabına öz şifrəsini qoya bilərdi — nömrəni
 * təsdiqləmək üçün SMS hələ qoşulmayıb.
 */
class AuthController extends WebController
{
    /** Bir ünvana saatda ən çox bu qədər kod — poçt qutusunu doldurmağa qarşı. */
    private const CODES_PER_HOUR = 5;

    /* ---------------- Giriş / çıxış ---------------- */

    public function showLogin()
    {
        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $data = $this->validateAz($request, [
            'email'    => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower(trim($data['email']));

        // Şifrə seçməyə qarşı: ünvan + IP üzrə dəqiqədə 5 səhv cəhd.
        $key = 'web-login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => 'Çox cəhd oldu. '.RateLimiter::availableIn($key).' saniyə sonra yenidən yoxla.',
            ]);
        }

        if (! Auth::guard('web')->attempt(['email' => $email, 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => 'E-poçt və ya şifrə yanlışdır.',
            ]);
        }

        RateLimiter::clear($key);

        if (Auth::guard('web')->user()->is_blocked) {
            Auth::guard('web')->logout();

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Hesabın bloklanıb. Ətraflı məlumat üçün bizimlə əlaqə saxla.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        // Sessiya bütövlükdə silinmir (`invalidate` yox): eyni brauzerdə admin
        // panelinə də daxil olunubsa, o giriş qalmalıdır. ID və CSRF tokeni
        // yenilənir — çıxışdan sonra köhnə sessiya ilə heç nə etmək olmur.
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /* ---------------- Qeydiyyat ---------------- */

    public function showRegister()
    {
        return view('frontend.auth.register');
    }

    /**
     * Formanı qəbul edib kodu göndərir. Hesab hələ yaradılmır — ad və şifrə
     * kodun yanında gözləyir (bax `EmailCode`).
     */
    public function register(Request $request)
    {
        $data = $this->validateAz($request, [
            'full_name' => ['required', 'string', 'min:2', 'max:100'],
            'email'     => ['required', 'string', 'email:rfc', 'max:191'],
            'password'  => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'terms'     => ['accepted'],
        ]);

        $email   = Str::lower(trim($data['email']));
        $payload = ['full_name' => trim($data['full_name']), 'password' => Hash::make($data['password'])];
        $input   = $request->only('full_name', 'email');

        if (User::withTrashed()->where('email', $email)->exists()) {
            return back()->withInput($input)->withErrors([
                'email' => 'Bu e-poçt artıq qeydiyyatdadır. Daxil ol və ya şifrəni bərpa et.',
            ]);
        }

        // Forma bir dəqiqə ərzində yenidən göndərilib (məsələn, "geri" düyməsi
        // ilə). Yeni məktub getmir — kod eynidir, yalnız ad və şifrə yenilənir.
        $pending = EmailCode::cooldown($email, 'register') > 0 ? EmailCode::current($email, 'register') : null;

        if ($pending) {
            $pending->update(['payload' => $payload]);
            $request->session()->put('register_email', $email);

            return redirect()->route('register.verify')
                ->with('info', 'Kod bir az əvvəl göndərilib — məktubdakı kodu yaz.');
        }

        if ($error = $this->sendCode($email, 'register', $payload, $payload['full_name'])) {
            return back()->withInput($input)->withErrors(['email' => $error]);
        }

        $request->session()->put('register_email', $email);

        return redirect()->route('register.verify')->with('info', "Kodu {$email} ünvanına göndərdik.");
    }

    public function showVerify(Request $request)
    {
        $email = $request->session()->get('register_email');

        if (! $email) {
            return redirect()->route('register');
        }

        return view('frontend.auth.verify', [
            'email'    => $email,
            'cooldown' => EmailCode::cooldown($email, 'register'),
        ]);
    }

    /** Kod düzdürsə hesabı yaradır və dərhal daxil edir. */
    public function verify(Request $request)
    {
        $email = $request->session()->get('register_email');

        if (! $email) {
            return redirect()->route('register');
        }

        $data   = $this->validateAz($request, ['code' => ['required', 'digits:6']]);
        $record = EmailCode::verify($email, 'register', $data['code']);

        if (! $record) {
            return back()->withErrors(['code' => 'Kod yanlışdır və ya vaxtı keçib.']);
        }

        try {
            $user = DB::transaction(function () use ($email, $record) {
                $user = new User();

                // Şifrə artıq hash-dir; `hashed` cast hash-lənmiş dəyəri ikinci
                // dəfə hash-ləmir.
                $user->forceFill([
                    'email'             => $email,
                    'email_verified_at' => now(),
                    'password'          => $record->payload['password'],
                    'full_name'         => $record->payload['full_name'],
                ])->save();

                // Tətbiqdə yaranan hər hesabda bu iki sətir var; sonra
                // birləşdirəndə sayt hesabı da eyni formada olsun.
                $user->stats()->create([]);
                $user->notificationPreference()->create([]);

                $record->delete();

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            // Eyni ünvan bu arada başqa tabda təsdiqlənib.
            $request->session()->forget('register_email');

            return redirect()->route('login')->withErrors(['email' => 'Bu e-poçt artıq qeydiyyatdadır. Daxil ol.']);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget('register_email');

        return redirect()->route('account')->with('success', 'Xoş gəldin! Hesabın yaradıldı.');
    }

    public function resend(Request $request)
    {
        $email = $request->session()->get('register_email');

        if (! $email) {
            return redirect()->route('register');
        }

        // Vaxtı keçmiş qeyd də götürülür: ad və şifrə hələ ondadır.
        $last = EmailCode::where('email', $email)->where('purpose', 'register')->latest('id')->first();

        if (! $last?->payload) {
            $request->session()->forget('register_email');

            return redirect()->route('register')->withErrors(['email' => 'Qeydiyyatı yenidən başla.']);
        }

        if ($wait = EmailCode::cooldown($email, 'register')) {
            return back()->withErrors(['code' => "Yeni kodu {$wait} saniyə sonra istəyə bilərsən."]);
        }

        if ($error = $this->sendCode($email, 'register', $last->payload, $last->payload['full_name'] ?? null)) {
            return back()->withErrors(['code' => $error]);
        }

        return back()->with('info', 'Yeni kod göndərildi.');
    }

    /* ---------------- Şifrə bərpası ---------------- */

    public function showForgot()
    {
        return view('frontend.auth.forgot');
    }

    public function sendReset(Request $request)
    {
        $data  = $this->validateAz($request, ['email' => ['required', 'string', 'email', 'max:191']]);
        $email = Str::lower(trim($data['email']));
        $user  = User::where('email', $email)->first();

        if ($user && ! $user->is_blocked && EmailCode::cooldown($email, 'reset') === 0) {
            if ($error = $this->sendCode($email, 'reset', null, $user->full_name)) {
                return back()->withInput()->withErrors(['email' => $error]);
            }
        }

        // Cavab hər halda eynidir: forma hansı ünvanın qeydiyyatda olduğunu
        // söyləməsin.
        $request->session()->put('reset_email', $email);

        return redirect()->route('password.reset')
            ->with('info', "{$email} qeydiyyatdadırsa, ona kod göndərdik.");
    }

    public function showReset(Request $request)
    {
        $email = $request->session()->get('reset_email');

        if (! $email) {
            return redirect()->route('password.forgot');
        }

        return view('frontend.auth.reset', ['email' => $email]);
    }

    public function reset(Request $request)
    {
        $email = $request->session()->get('reset_email');

        if (! $email) {
            return redirect()->route('password.forgot');
        }

        $data = $this->validateAz($request, [
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        $record = EmailCode::verify($email, 'reset', $data['code']);
        $user   = $record ? User::where('email', $email)->first() : null;

        if (! $user || $user->is_blocked) {
            return back()->withErrors(['code' => 'Kod yanlışdır və ya vaxtı keçib.']);
        }

        DB::transaction(function () use ($user, $record, $data) {
            // Yeni "məni xatırla" tokeni — köhnə şifrə ilə başqa cihazda
            // xatırlanmış giriş artıq işləmir.
            $user->forceFill([
                'password'       => $data['password'],
                'remember_token' => Str::random(60),
            ])->save();

            $record->delete();
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget('reset_email');

        return redirect()->route('account')->with('success', 'Şifrən yeniləndi.');
    }

    /* ---------------- Kod göndərmə ---------------- */

    /**
     * Kodu yaradıb göndərir. Uğursuz olanda istifadəçiyə göstəriləcək mətni
     * qaytarır, uğurlu olanda null.
     */
    private function sendCode(string $email, string $purpose, ?array $payload, ?string $name): ?string
    {
        $hourKey = 'email-codes:'.$email;

        if (RateLimiter::tooManyAttempts($hourKey, self::CODES_PER_HOUR)) {
            return 'Bu ünvana çox kod göndərildi. Bir saat sonra yenidən yoxla.';
        }

        $code = EmailCode::issue($email, $purpose, $payload);

        try {
            Mail::to($email)->send(new EmailCodeMail($code, $purpose, $name));
        } catch (\Throwable $e) {
            Log::error('Email code could not be sent', [
                'purpose' => $purpose,
                'error'   => $e->getMessage(),
            ]);

            // Kod silinir ki, gözləmə müddəti istifadəçini saxlamasın — heç
            // nə getməyibsə, dərhal yenidən cəhd edə bilməlidir.
            EmailCode::where('email', $email)->where('purpose', $purpose)->delete();

            return 'Məktubu göndərmək alınmadı. Bir az sonra yenidən yoxla.';
        }

        RateLimiter::hit($hourKey, 3600);

        return null;
    }
}
