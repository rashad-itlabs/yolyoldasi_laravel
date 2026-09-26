<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Token varsa istifadəçini tanıyır, yoxdursa buraxır.
 *
 * Axtarış və ictimai profil endpoint-ləri üçün lazımdır. Onlar `auth:sanctum`
 * altında olanda tətbiqi ilk dəfə açan adam **bir dənə də səfər görmədən**
 * telefon nömrəsini verməli olurdu — funnel-in ən böyük itkisi məhz orada idi.
 * İnsan malı görmədən nömrəsini vermir.
 *
 * Eyni zamanda token gələndə istifadəçini tanımaq da vacibdir: `is_mine`,
 * `plate`-in görünməsi və axtarış tarixçəsinin yazılması buna baxır.
 *
 * Guard yox, token sətri birbaşa oxunur. `Auth::guard('sanctum')` bu layihənin
 * `config/auth.php`-ında elan edilməyib (paketin özü əlavə edir) — ondan asılı
 * olmamaq bu middleware-i konfiqurasiyanın dəyişməsindən qoruyur.
 *
 * Route-da tam sinif adı ilə işlədilir, alias olmadan:
 *
 *     Route::get('/rides', ...)->middleware(OptionalSanctum::class);
 */
class OptionalSanctum
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();

        if ($bearer === null || $bearer === '') {
            return $next($request);
        }

        $token = PersonalAccessToken::findToken($bearer);

        // Etibarsız və ya vaxtı keçmiş token 401 vermir, sadəcə tanınmır:
        // bu endpoint-lər qonağa da açıqdır və köhnə token ucbatından
        // axtarışın bağlanması istifadəçi üçün izaholunmaz olardı. Sessiyanın
        // bitdiyini `GET /me` onsuz da ilk fürsətdə bildirir.
        if (! $token || ($token->expires_at !== null && $token->expires_at->isPast())) {
            return $next($request);
        }

        $user = $token->tokenable;

        if ($user) {
            $token->forceFill(['last_used_at' => now()])->save();

            $user->withAccessToken($token);

            $request->setUserResolver(fn () => $user);
        }

        return $next($request);
    }
}
