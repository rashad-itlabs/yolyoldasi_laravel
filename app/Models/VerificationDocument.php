<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'driver_profile_id', 'type', 'status', 'file_url', 'back_file_url',
        'uploaded_at', 'reviewed_at', 'rejection_reason',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public const TYPES = ['id_card', 'driver_license', 'vehicle_registration', 'insurance'];

    /** Yalnız bu iki sənədin arxa üzü olur. */
    public const TWO_SIDED = ['id_card', 'driver_license'];

    public function driverProfile()
    {
        return $this->belongsTo(DriverProfile::class);
    }
}
