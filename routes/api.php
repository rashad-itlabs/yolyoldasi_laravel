<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\DemandController;
use App\Http\Controllers\Api\DevAuthController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecentSearchController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\RideRequestController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Middleware\OptionalSanctum;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /* ---------------- Açıq ---------------- */

    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'time'   => now()->toIso8601String(),
    ]));

    Route::get('/cities', [CityController::class, 'index']);

    // Yeniləmə divarı giriş ekranından da əvvəl işə düşür, ona görə açıqdır.
    // Tətbiq hər açılışda və fondan qayıdanda soruşur — limit buna görə boldur.
    Route::get('/app-version', [AppVersionController::class, 'show'])
        ->middleware('throttle:60,1');

    Route::post('/auth/firebase', [AuthController::class, 'firebase'])
        ->middleware('throttle:10,1');

    // SMS kodu ilə giriş (Flutter). SMS provayderi qoşulana qədər kod
    // birinci addımın cavabında qayıdır — bax config/dev.php.
    Route::post('/auth/phone/request', [AuthController::class, 'requestCode'])
        ->middleware('throttle:10,1');

    Route::post('/auth/phone/verify', [AuthController::class, 'verifyCode'])
        ->middleware('throttle:20,1');

    // MÜVƏQQƏTİ: SMS-siz token. DEV_AUTH_ENABLED=false olanda 404 verir.
    // İstismardan əvvəl bu sətri və DevAuthController-i sil.
    Route::post('/auth/dev', [DevAuthController::class, 'login'])
        ->middleware('throttle:20,1');

    // MÜVƏQQƏTİ diaqnostika — başlıqların serverə necə çatdığını göstərir.
    Route::get('/debug/headers', [DevAuthController::class, 'headers']);

    /* ---------- Girişsiz baxış (token varsa tanınır) ----------
     |
     | Bu üç endpoint qəsdən açıqdır. Əvvəl hamısı `auth:sanctum` altında idi
     | və tətbiqi ilk dəfə açan adam bir dənə də səfər görmədən nömrəsini
     | verməli olurdu — funnel-in ən böyük itkisi orada idi. İnsan malı
     | görmədən nömrəsini vermir.
     |
     | Giriş yalnız **bron etmək** üçün tələb olunur; `OptionalSanctum` isə
     | token gələndə istifadəçini tanıyır ki, `is_mine`, avtomobil nömrəsi və
     | axtarış tarixçəsi düzgün işləsin.
     */
    Route::middleware([OptionalSanctum::class, 'throttle:120,1'])->group(function () {
        Route::get('/rides', [RideController::class, 'index']);

        // `whereNumber` olmadan bu sətir aşağıdakı `/rides/mine`-i udardı:
        // açıq qrup əvvəl elan olunub və "mine" sözü {ride} kimi oxunardı.
        Route::get('/rides/{ride}', [RideController::class, 'show'])->whereNumber('ride');
        Route::get('/users/{user}', [ReviewController::class, 'profile']);
        Route::get('/users/{user}/reviews', [ReviewController::class, 'index']);

        // Telemetriya girişdən əvvəlki addımları da tutmalıdır — funnel-in
        // ən maraqlı hissəsi məhz oradadır.
        Route::post('/events', [EventController::class, 'store']);
    });

    /* ---------------- Qorunan ---------------- */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/account', [AuthController::class, 'deleteAccount']);

        /* Profil */
        Route::get('/me', [ProfileController::class, 'show']);
        Route::patch('/me', [ProfileController::class, 'update']);
        Route::put('/me/mode', [ProfileController::class, 'updateMode']);
        Route::post('/me/photo', [ProfileController::class, 'uploadPhoto']);
        Route::get('/me/notification-preferences', [ProfileController::class, 'notificationPreferences']);
        Route::put('/me/notification-preferences', [ProfileController::class, 'updateNotificationPreferences']);

        /* Cihaz tokenləri (FCM) */
        Route::post('/me/device-tokens', [DeviceTokenController::class, 'store']);
        Route::delete('/me/device-tokens', [DeviceTokenController::class, 'destroy']);

        /* Axtarış tarixçəsi */
        Route::get('/me/recent-searches', [RecentSearchController::class, 'index']);
        Route::delete('/me/recent-searches', [RecentSearchController::class, 'destroy']);

        /* Dəvət kodu — "Dostunu dəvət et" ekranı */
        Route::get('/me/referral', [ProfileController::class, 'referral']);

        /* Sürücü profili və sənədlər */
        Route::get('/driver/profile', [DriverController::class, 'profile']);
        Route::put('/driver/profile', [DriverController::class, 'updateProfile']);
        Route::get('/driver/documents', [DriverController::class, 'documents']);
        Route::post('/driver/documents', [DriverController::class, 'uploadDocument']);

        /* Avtomobillər */
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy']);

        /* Səfərlər — `index` və `show` yuxarıdakı açıq qrupdadır. */
        Route::post('/rides', [RideController::class, 'store']);
        Route::get('/rides/mine', [RideController::class, 'mine']);
        Route::put('/rides/{ride}', [RideController::class, 'update']);
        Route::delete('/rides/{ride}', [RideController::class, 'destroy']);
        Route::post('/rides/{ride}/complete', [RideController::class, 'complete']);

        // Eyni marşrutu yenidən elan etmək — üç addımlı forma iki toxunuşa
        // enir. Sürücünün ikinci elanı bazarın sağlamlığının əsas göstəricisidir.
        Route::post('/rides/{ride}/repeat', [RideController::class, 'repeat']);

        /* Tələb elanları — bazarın sərnişin tərəfi */
        Route::get('/ride-requests', [RideRequestController::class, 'index']);
        Route::post('/ride-requests', [RideRequestController::class, 'store']);
        Route::get('/ride-requests/incoming', [RideRequestController::class, 'incoming']);
        Route::get('/ride-requests/{rideRequest}', [RideRequestController::class, 'show'])
            ->whereNumber('rideRequest');
        Route::delete('/ride-requests/{rideRequest}', [RideRequestController::class, 'destroy']);

        /* Tələb statistikası və qiymət təklifi — sürücü üçün */
        Route::get('/demand', [DemandController::class, 'show']);
        Route::get('/demand/top', [DemandController::class, 'top']);
        Route::get('/price-suggestion', [DemandController::class, 'price']);

        /* Rezervasiyalar */
        Route::post('/rides/{ride}/bookings', [BookingController::class, 'store']);
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/incoming', [BookingController::class, 'incoming']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
        Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        /* Söhbətlər */
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount']);
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
        Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markRead']);

        /* Rəylər — ictimai profil və rəy siyahısı açıq qrupdadır. */
        Route::post('/bookings/{booking}/reviews', [ReviewController::class, 'store']);

        /* Bildirişlər */
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

        /* Şikayətlər */
        Route::post('/reports', [ReportController::class, 'store']);
    });

});
