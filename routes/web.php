<?php
use App\Http\Controllers\AppLinksController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RideShareController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AuthController as WebAuthController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/*
| Sayt formalarının limitləri. Adlıdır, çünki `throttle:10,1` kimi adsız limit
| IP üzrə sayır və API-nin `throttle:60,1`-i ilə EYNİ sayğacı bölüşür: tətbiq
| açıq olan telefonla eyni Wi-Fi-da olan brauzer giriş formasında 429 alardı.
|
| `web-mail` məktub göndərən formalardır — bir IP-dən çoxlu ünvana kod atıb
| Gmail-in gündəlik limitini yandırmağa qarşı saatlıq hədd də var.
*/
RateLimiter::for('web-auth', fn (Request $request) => Limit::perMinute(20)->by('web-auth|'.$request->ip()));
RateLimiter::for('web-mail', fn (Request $request) => [
    Limit::perMinute(5)->by('web-mail-m|'.$request->ip()),
    Limit::perHour(20)->by('web-mail-h|'.$request->ip()),
]);

Route::get('/',[HomeController::class,'home']);

// Səfərin paylaşma səhifəsi. Ünvan qəsdən qısadır — WhatsApp və Telegram
// mesajının içinə düşür, `/rides/7` deyil, `/r/7`.
Route::get('/r/{ride}', [RideShareController::class, 'show'])
    ->whereNumber('ride')
    ->name('ride.share');

// Yuxarıdakı linkin telefonda tətbiqi açması üçün — AppLinksController-ə bax.
Route::get('/.well-known/assetlinks.json', [AppLinksController::class, 'android']);
Route::get('/.well-known/apple-app-site-association', [AppLinksController::class, 'apple']);
Route::get('/contact',[HomeController::class,'contact'])->name('contact');
Route::get('/privacy-policy',[HomeController::class,'privacy'])->name('privacy-policy');
Route::get('/terms-of-use',[HomeController::class,'term'])->name('terms-of-use');
Route::get('/account-deletion',[HomeController::class,'accountDeletion'])->name('account-deletion');
Route::get('/haqqimizda',[HomeController::class,'about'])->name('about');

/*
| Saytın hesabı — e-poçt + şifrə, qeydiyyat e-poçta gedən kodla.
| Hələlik tətbiq hesablarından ayrıdır (tətbiq telefonla girir).
|
| `login` adı qəsdən belədir: `auth` middleware-i daxil olmamış qonağı
| `route('login')`-ə yönləndirir (bax `bootstrap/app.php`).
*/
Route::middleware('guest')->group(function () {
    Route::get('/giris', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/giris', [WebAuthController::class, 'login'])->middleware('throttle:web-auth')->name('login.attempt');

    Route::get('/qeydiyyat', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/qeydiyyat', [WebAuthController::class, 'register'])->middleware('throttle:web-mail')->name('register.send');
    Route::get('/qeydiyyat/tesdiq', [WebAuthController::class, 'showVerify'])->name('register.verify');
    Route::post('/qeydiyyat/tesdiq', [WebAuthController::class, 'verify'])->middleware('throttle:web-auth')->name('register.confirm');
    Route::post('/qeydiyyat/yeniden', [WebAuthController::class, 'resend'])->middleware('throttle:web-mail')->name('register.resend');

    Route::get('/sifreni-unutdum', [WebAuthController::class, 'showForgot'])->name('password.forgot');
    Route::post('/sifreni-unutdum', [WebAuthController::class, 'sendReset'])->middleware('throttle:web-mail')->name('password.email');
    Route::get('/sifreni-yenile', [WebAuthController::class, 'showReset'])->name('password.reset');
    Route::post('/sifreni-yenile', [WebAuthController::class, 'reset'])->middleware('throttle:web-auth')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/cixis', [WebAuthController::class, 'logout'])->name('logout');

    Route::get('/hesab', [AccountController::class, 'show'])->name('account');
    Route::get('/hesab/redakte', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/hesab', [AccountController::class, 'update'])->name('account.update');
    Route::put('/hesab/sifre', [AccountController::class, 'password'])->name('account.password');
    Route::get('/hesab/seferler', [AccountController::class, 'rides'])->name('account.rides');
});

// Köhnə ünvan — indekslənmiş linklər üçün.
Route::redirect('/register', '/qeydiyyat', 301);

// Admin paneli — `web` qrupunun içində qalsın deyə buradan qoşulur.
require __DIR__.'/admin.php';
