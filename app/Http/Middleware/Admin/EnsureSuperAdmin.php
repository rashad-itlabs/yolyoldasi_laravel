<?php

namespace App\Http\Middleware\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Admin idarəetməsi yalnız baş adminə açıqdır. */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            Auth::guard('admin')->user()?->isSuperAdmin(),
            403,
            'Bu bölmə yalnız baş admin üçündür.'
        );

        return $next($request);
    }
}
