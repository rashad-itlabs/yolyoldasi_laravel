<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'reporter_id', 'target_user_id', 'reason', 'status', 'details',
        'ride_id', 'booking_id', 'resolved_at', 'resolved_by', 'admin_note',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    public const REASONS = [
        'no_show', 'unsafe_driving', 'rude_behaviour',
        'wrong_vehicle', 'price_dispute', 'spam', 'other',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
