<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'type', 'ride_id', 'booking_id', 'conversation_id',
        'actor_id', 'payload', 'read_at', 'push_message_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
