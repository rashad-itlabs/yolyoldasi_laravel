<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'instant_booking_default',
    ];

    protected $casts = [
        'submitted_at'            => 'datetime',
        'reviewed_at'             => 'datetime',
        'instant_booking_default' => 'boolean',
        // Dəvət mükafatının bitmə vaxtı. Cast olmadan sütun sətir kimi gəlir
        // və `ReferralService`-dəki `isFuture()` çağırışı xəta verərdi.
        'boost_until'             => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function documents()
    {
        return $this->hasMany(VerificationDocument::class);
    }
}
