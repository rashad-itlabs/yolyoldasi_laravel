<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'phone',
        'firebase_uid',
        'phone_verified_at',
        'full_name',
        'photo_url',
        'about',
        'city_id',
        'gender',
        'birth_year',
        'active_mode',
        'has_driver_profile',
        'language_code',
    ];

    /*
     | `onesignal_external_id` qəsdən `fillable` deyil. Dəyər həmişə hesabın öz
     | id-sidir və yalnız `DeviceTokenController` onu yazır (`forceFill` ilə);
     | kütləvi doldurmaya açıq olsa, `PATCH /me` sorğusuna əlavə edilmiş bir
     | sahə bu hesabın bildirişlərini başqasının telefonuna yönləndirə bilərdi.
     |
     | `referral_code`, `referred_by` və `referred_at` eyni səbəbdən kənardadır:
     | kodu server verir, dəvəti isə yalnız `AuthController` qeydiyyat anında
     | bağlayır. `PATCH /me` ilə dəyişdirilə bilsəydi, istifadəçi öz kodunu
     | başqasınınkı ilə əvəz edib mükafatı oğurlaya bilərdi.
     |
     | `email`, `email_verified_at` və `password` də kənardadır: onları yalnız
     | saytın `Web\AuthController`-i kod təsdiqləndikdən sonra yazır. `PATCH /me`
     | ilə açıq olsaydılar, tətbiq hesabına təsdiqsiz e-poçt və şifrə qoyub onu
     | saytdan ələ keçirmək olardı.
     */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at'   => 'datetime',
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'blocked_at'          => 'datetime',
            'onesignal_linked_at'  => 'datetime',
            'referred_at'          => 'datetime',
            'referral_rewarded_at' => 'datetime',
            'has_driver_profile'  => 'boolean',
            'is_admin'            => 'boolean',
            'is_blocked'          => 'boolean',
        ];
    }

    /**
     * Hər yeni hesaba dəvət kodu verilir.
     *
     * Kod hesabın yaranma anında doğulur, "Dostunu dəvət et" ekranı ilk dəfə
     * açılanda yox: sonrakı halda kodu olmayan köhnə hesablar yaranardı və
     * paylaşma düyməsi bəzən boş kod göstərərdi.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->referral_code = $user->referral_code ?: self::generateReferralCode();
        });
    }

    /**
     * Oxunaqlı dəvət kodu.
     *
     * Əlifbada `0/O` və `1/I/L` yoxdur: kod səslə deyilir və WhatsApp-da əl ilə
     * yazılır, qarışan simvol birbaşa itirilmiş dəvət deməkdir.
     */
    public static function generateReferralCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::withTrashed()->where('referral_code', $code)->exists());

        return $code;
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function stats()
    {
        return $this->hasOne(UserStat::class);
    }

    public function notificationPreference()
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function driverProfile()
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function rides()
    {
        return $this->hasMany(Ride::class, 'driver_id');
    }

    /** Sərnişin kimi verdiyi rezervasiyalar. */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'passenger_id');
    }

    /** Sürücü kimi ona gələn rezervasiyalar. */
    public function incomingBookings()
    {
        return $this->hasMany(Booking::class, 'driver_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot('unread_count', 'last_read_at');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'target_id');
    }

    public function recentSearches()
    {
        return $this->hasMany(RecentSearch::class);
    }

    /** Sərnişin kimi verdiyi tələb elanları. */
    public function rideRequests()
    {
        return $this->hasMany(RideRequest::class);
    }

    /** Bu hesabı dəvət etmiş istifadəçi. */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Bu hesabın dəvət etdikləri. */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }
}
