<?php

namespace App\Http\Controllers\Web;

use App\Models\Booking;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Saytda daxil olmuş istifadəçinin hesabı: profil və səfərlər.
 *
 * Elan yerləşdirmək və bron etmək tətbiqdə qalır — burada yalnız baxış və
 * profil redaktəsi var.
 */
class AccountController extends WebController
{
    public function show(Request $request)
    {
        return view('frontend.account.show', [
            'user' => $request->user()->load('city'),
        ]);
    }

    public function edit(Request $request)
    {
        return view('frontend.account.edit', [
            'user'   => $request->user(),
            'cities' => City::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $this->validateAz($request, [
            'full_name'  => ['required', 'string', 'min:2', 'max:100'],
            'city_id'    => ['nullable', 'integer', 'exists:cities,id'],
            'gender'     => ['required', 'in:male,female,unspecified'],
            'birth_year' => ['nullable', 'integer', 'between:1940,'.(now()->year - 18)],
            'about'      => ['nullable', 'string', 'max:300'],
        ]);

        $request->user()->update($data);

        return redirect()->route('account')->with('success', 'Profil yeniləndi.');
    }

    public function password(Request $request)
    {
        $data = $this->validateAz($request, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password'         => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ], 'password');

        // Yeni "məni xatırla" tokeni: başqa cihazda köhnə şifrə ilə xatırlanmış
        // giriş bağlanır, bu sessiya isə davam edir.
        $request->user()->forceFill([
            'password'       => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();

        return redirect()->to(route('account.edit').'#sifre')->with('password_success', 'Şifrə dəyişdirildi.');
    }

    public function rides(Request $request)
    {
        $user = $request->user();

        $rides = $user->rides()
            ->with(['fromCity', 'toCity'])
            ->withCount(['bookings' => fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed'])])
            ->latest('departure_at')
            ->limit(50)
            ->get();

        $bookings = Booking::with(['ride.fromCity', 'ride.toCity', 'ride.driver'])
            ->where('passenger_id', $user->id)
            ->latest()
            ->limit(50)
            ->get();

        return view('frontend.account.rides', compact('rides', 'bookings'));
    }
}
