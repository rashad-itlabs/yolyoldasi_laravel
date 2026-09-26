<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Cihazı qeyd edir.
     *
     * `token` artıq OneSignal subscription id-sidir; formasına baxıb FCM
     * tokenindən ayırd etmək mümkün olmadığı üçün `provider` hansı olduğunu
     * deyir. Köhnə tətbiq versiyaları bu sahəni göndərmir — onlar üçün
     * defolt 'fcm'-dir, çünki OneSignal-dan əvvəlki hər şey FCM idi.
     *
     * Token cihazla birlikdə hesablar arasında köçdüyü üçün create yox,
     * updateOrCreate işlədilir — əks halda unique constraint xətası olur.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'       => ['required', 'string', 'max:512'],
            'platform'    => ['required', 'in:android,ios'],
            'provider'    => ['sometimes', 'in:onesignal,fcm'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        $user     = $request->user();
        $provider = $data['provider'] ?? 'fcm';

        // Tətbiq girişdən sonra `OneSignal.login(user_id)` çağırır və həmin
        // dəyəri buraya göndərir. Bundan sonra bildirişi cihaz siyahısına
        // yox, birbaşa hesaba ünvanlamaq olur — bax `OneSignalService`.
        //
        // Dəyər öz id-si ilə tutuşdurulur: alias hesabı ünvanlayır, yanlışı
        // isə bu istifadəçinin bildirişlərini başqasının telefonuna
        // yönləndirərdi.
        $externalId = ($data['external_id'] ?? null) === (string) $user->id
            ? (string) $user->id
            : null;

        if ($provider === 'onesignal' && $externalId !== null) {
            $user->forceFill([
                'onesignal_external_id' => $externalId,
                'onesignal_linked_at'   => now(),
            ])->save();
        }

        $token = DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $user->id,
                'platform'     => $data['platform'],
                'provider'     => $provider,
                'external_id'  => $externalId,
                'last_used_at' => now(),
            ]
        );

        return response()->json(['data' => $token], 201);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        DeviceToken::where('token', $data['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        // Alias qəsdən saxlanılır. Tətbiq çıxışdan əvvəl `OneSignal.logout()`
        // çağırır, yəni bu cihaz artıq həmin hesabın adı altında görünmür;
        // alias-ı burada silmək isə eyni hesabın *digər* cihazlarına gedən
        // bildirişləri də kəsərdi.
        return response()->json(['message' => 'Token silindi.']);
    }
}
