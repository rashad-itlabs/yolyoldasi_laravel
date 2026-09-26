<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        // 'onesignal' | 'fcm' — `token`-in nə olduğunu deyir. OneSignal-da o,
        // subscription id-dir, FCM-də registration token; formasına baxıb
        // ayırd etmək mümkün deyil və ikisi fərqli API ilə ünvanlanır.
        'provider',
        // Cihazın hansı hesabla OneSignal-a login olduğu.
        'external_id',
        'last_used_at',
    ];

    protected $casts = ['last_used_at' => 'datetime'];

    public function scopeOneSignal($query)
    {
        return $query->where('provider', 'onesignal');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
