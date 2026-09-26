<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Panelin bütün səhifələrini qoruyur.
     *
     * Deaktiv edilmiş admin də buradan kəsilir — `is_active` yalnız girişdə
     * yoxlansaydı, açıq sessiyası olan admin hüququ alınandan sonra da
     * işləməyə davam edərdi.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if (! $guard->check()) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $guard->user()->is_active) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Hesabınız deaktiv edilib.']);
        }

        return $next($request);
    }
}
