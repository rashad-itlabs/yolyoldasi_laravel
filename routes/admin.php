<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AppVersionController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PushController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RideController;
use App\Http\Controllers\Admin\RideRequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\Admin\Authenticate as AdminAuth;
use App\Http\Middleware\Admin\EnsureSuperAdmin;
use App\Http\Middleware\Admin\RedirectIfAuthenticated as RedirectAuthenticatedAdmin;
use Illuminate\Support\Facades\Route;

/*
| Admin paneli. Bu fayl routes/web.php-dən require olunur, ona görə bütün
| yollar `web` middleware qrupundadır (sessiya + CSRF).
|
| Ad prefiksi `admin.` — middleware `admin.login`-ə yönləndirir.
*/

Route::prefix('admin')->name('admin.')->group(function () {

    /* ---------------- Giriş ---------------- */

    Route::middleware(RedirectAuthenticatedAdmin::class)->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1')
            ->name('login.attempt');
    });

    /* ---------------- Qorunan ---------------- */

    Route::middleware(AdminAuth::class)->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        /* İstifadəçilər */
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{id}', [UserController::class, 'show'])->whereNumber('id')->name('users.show');
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{id}/restore', [UserController::class, 'restore'])->whereNumber('id')->name('users.restore');

        // Token vermək = həmin hesabın adından API-yə tam giriş, ona görə
        // yalnız baş admin. Ləğv etmək təhlükəsizlik addımıdır — hamıya açıqdır.
        Route::post('users/{user}/tokens', [UserController::class, 'storeToken'])
            ->middleware(EnsureSuperAdmin::class)
            ->name('users.tokens.store');
        Route::delete('users/{user}/tokens/{token}', [UserController::class, 'destroyToken'])
            ->whereNumber('token')
            ->name('users.tokens.destroy');

        /* Sürücü təsdiqi */
        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
        Route::post('drivers/{driver}/approve', [DriverController::class, 'approveAll'])->name('drivers.approve');
        Route::post('drivers/{driver}/reject', [DriverController::class, 'reject'])->name('drivers.reject');
        Route::post('documents/{document}/approve', [DriverController::class, 'approveDocument'])->name('documents.approve');
        Route::post('documents/{document}/reject', [DriverController::class, 'rejectDocument'])->name('documents.reject');
        Route::get('documents/{document}/file/{side?}', [DriverController::class, 'document'])->name('documents.file');

        /* Səfərlər */
        Route::get('rides', [RideController::class, 'index'])->name('rides.index');
        Route::get('rides/{ride}', [RideController::class, 'show'])->name('rides.show');
        Route::post('rides/{ride}/cancel', [RideController::class, 'cancel'])->name('rides.cancel');
        Route::post('rides/{ride}/deactivate', [RideController::class, 'deactivate'])->name('rides.deactivate');
        Route::post('rides/{ride}/activate', [RideController::class, 'activate'])->name('rides.activate');
        Route::delete('rides/{ride}', [RideController::class, 'destroy'])->name('rides.destroy');

        /* Tələb elanları — sərnişinin "yer axtarıram" elanı, yalnız baxış */
        Route::get('ride-requests', [RideRequestController::class, 'index'])->name('ride-requests.index');

        /* Rezervasiyalar */
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

        /* Şikayətlər */
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
        Route::post('reports/{report}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
        Route::post('reports/{report}/dismiss', [ReportController::class, 'dismiss'])->name('reports.dismiss');
        Route::post('reports/{report}/reopen', [ReportController::class, 'reopen'])->name('reports.reopen');

        /* Rəylər */
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        /* Məzmun — əlaqə, səhifələr, FAQ */
        Route::get('contact', [ContactController::class, 'edit'])->name('contact.edit');
        Route::put('contact', [ContactController::class, 'update'])->name('contact.update');

        Route::get('pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');

        Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
        Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
        Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
        Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
        Route::post('faqs/{faq}/toggle', [FaqController::class, 'toggle'])->name('faqs.toggle');
        Route::post('faqs/{faq}/move', [FaqController::class, 'move'])->name('faqs.move');

        /* Şəhərlər */
        Route::get('cities', [CityController::class, 'index'])->name('cities.index');
        Route::post('cities', [CityController::class, 'store'])->name('cities.store');
        Route::put('cities/{city}', [CityController::class, 'update'])->name('cities.update');
        Route::post('cities/{city}/toggle', [CityController::class, 'toggle'])->name('cities.toggle');
        Route::delete('cities/{city}', [CityController::class, 'destroy'])->name('cities.destroy');

        /* Mobil tətbiqin versiyası — məcburi yeniləmə divarı */
        Route::get('app-version', [AppVersionController::class, 'edit'])->name('app-version.edit');
        Route::put('app-version', [AppVersionController::class, 'update'])->name('app-version.update');

        /* Push bildirişlər */
        Route::get('push', [PushController::class, 'index'])->name('push.index');
        Route::post('push', [PushController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('push.store');

        /* Fəaliyyət jurnalı */
        Route::get('logs', [ActivityLogController::class, 'index'])->name('logs.index');

        /* Adminlər — yalnız baş admin */
        Route::middleware(EnsureSuperAdmin::class)->group(function () {
            Route::get('admins', [AdminUserController::class, 'index'])->name('admins.index');
            Route::get('admins/create', [AdminUserController::class, 'create'])->name('admins.create');
            Route::post('admins', [AdminUserController::class, 'store'])->name('admins.store');
            Route::get('admins/{admin}/edit', [AdminUserController::class, 'edit'])->name('admins.edit');
            Route::put('admins/{admin}', [AdminUserController::class, 'update'])->name('admins.update');
            Route::delete('admins/{admin}', [AdminUserController::class, 'destroy'])->name('admins.destroy');
        });
    });
});
