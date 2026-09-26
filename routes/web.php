<?php
use App\Http\Controllers\AppLinksController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RideShareController;
use Illuminate\Support\Facades\Route;

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

// Veb qeydiyyat ləğv edildi — köhnə linklər və indekslənmiş səhifə üçün
Route::redirect('/register', '/#yukle', 301);

// Admin paneli — `web` qrupunun içində qalsın deyə buradan qoşulur.
require __DIR__.'/admin.php';
