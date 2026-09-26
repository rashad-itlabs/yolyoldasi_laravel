<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'ride_id', 'passenger_id', 'driver_id', 'seats', 'total_price', 'status',
        'message', 'decided_at', 'cancelled_at', 'cancellation_reason',
        'passenger_reviewed', 'driver_reviewed',
    ];

    protected $casts = [
        'total_price'        => 'decimal:2',
        'decided_at'         => 'datetime',
        'cancelled_at'       => 'datetime',
        'passenger_reviewed' => 'boolean',
        'driver_reviewed'    => 'boolean',
    ];

    /** Yeri tutan statuslar — booked_seats bunlara görə hesablanır. */
    public const SEAT_HOLDING = ['confirmed', 'completed'];

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function passenger()
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
