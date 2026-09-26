<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'push_enabled',
        'bookings',
        'messages',
        'reminders',
        'marketing',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'bookings'     => 'boolean',
        'messages'     => 'boolean',
        'reminders'    => 'boolean',
        'marketing'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
