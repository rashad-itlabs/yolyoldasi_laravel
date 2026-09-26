<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // is_active şərti birbaşa buraya qoyulur ki, deaktiv hesab heç
        // vaxt sessiya açmasın — sonradan yoxlamaq gec olur.
        $credentials = [
            'email'     => $data['email'],
            'password'  => $data['password'],
            'is_active' => true,
        ];

        if (! Auth::guard('admin')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'E-poçt və ya şifrə yanlışdır.',
            ]);
        }

        $request->session()->regenerate();

        $admin = Auth::guard('admin')->user();
        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        AdminActivityLog::record('admin.login', $admin);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        AdminActivityLog::record('admin.logout', Auth::guard('admin')->user());

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
