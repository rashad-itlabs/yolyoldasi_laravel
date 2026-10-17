<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return new UserResource(
            $request->user()->load(['city', 'stats', 'notificationPreference'])
        );
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'full_name'     => ['sometimes', 'string', 'min:3', 'max:255'],
            'about'         => ['sometimes', 'nullable', 'string', 'max:300'],
            'gender'        => ['sometimes', 'in:male,female,unspecified'],
            'birth_year'    => ['sometimes', 'nullable', 'integer', 'min:1930', 'max:'.(date('Y') - 16)],
            'city_id'       => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'language_code' => ['sometimes', 'in:az,en,ru'],
        ]);

        $user = $request->user();
        $user->update($data);

        return new UserResource($user->load(['city', 'stats']));
    }

    /** Sərnişin ↔ sürücü rejimi arasında keçid. */
    public function updateMode(Request $request)
    {
        $data = $request->validate([
            'active_mode' => ['required', 'in:passenger,driver'],
        ]);

        $user = $request->user();

        abort_if(
            $data['active_mode'] === 'driver' && ! $user->has_driver_profile,
            422,
            'Əvvəlcə sürücü profilini yarat.'
        );

        $user->update($data);

        // Rejim keçidi artıq profil ekranından da edilir, təkcə girişdən yox:
        // rayona gedən adam cümə sürücü, bazar ertəsi sərnişindir və keçid
        // üçün çıxıb yenidən OTP ilə girmək tələb etmək hər istifadəçinin
        // dəyərini yarıya endirirdi. Cavab tam profildir ki, klient sessiyanı
        // ikinci sorğu etmədən yeniləyə bilsin.
        return new UserResource($user->load(['city', 'stats', 'notificationPreference']));
    }

    /**
     * "Dostunu dəvət et" ekranının məlumatı.
     *
     * Mükafat pul deyil, **görünürlükdür**: dəvət olunan ilk səfərini edəndə
     * dəvət edənin elanları bir müddət axtarışın başında çıxır. Ödəniş qatı
     * olmayan məhsulda nağd mükafat həm saxta hesab axını yaradar, həm də
     * dayandırılan gün istifadəçini incidər.
     */
    public function referral(Request $request, ReferralService $referrals)
    {
        return response()->json([
            'data' => $referrals->summary($request->user()),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();

        // Köhnə şəkli silirik ki, disk lazımsız fayllarla dolmasın.
        $oldPath = parse_url((string) $user->photo_url, PHP_URL_PATH);

        if ($oldPath && str_starts_with($oldPath, '/storage/')) {
            Storage::disk('public')->delete(substr($oldPath, strlen('/storage/')));
        }

        $path = $request->file('photo')->store('avatars', 'public');

        // `public` diskin URL-i, `Storage::url()` yox: o, standart (`local`)
        // diskə baxır və nisbi `/storage/…` qaytarır — tətbiq onu şəkil kimi
        // yükləyə bilmirdi. `public` diskin `url`-i tam domenlidir.
        $user->update(['photo_url' => Storage::disk('public')->url($path)]);

        return new UserResource($user);
    }

    public function notificationPreferences(Request $request)
    {
        return response()->json([
            'data' => $request->user()->notificationPreference()->firstOrCreate([]),
        ]);
    }

    public function updateNotificationPreferences(Request $request)
    {
        $data = $request->validate([
            'push_enabled' => ['sometimes', 'boolean'],
            'bookings'     => ['sometimes', 'boolean'],
            'messages'     => ['sometimes', 'boolean'],
            'reminders'    => ['sometimes', 'boolean'],
            'marketing'    => ['sometimes', 'boolean'],
        ]);

        $prefs = $request->user()->notificationPreference()->firstOrCreate([]);

        // Kompozit olmayan, amma qeyri-artımlı açar — sorğu qurucusu ilə yeniləyirik.
        $request->user()->notificationPreference()->update($data);

        return response()->json(['data' => $prefs->fresh()]);
    }
}
