<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'booking_id', 'ride_id', 'last_message', 'last_message_at',
        'last_sender_id', 'is_locked',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_locked'       => 'boolean',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function ride()
    {
        return $this->belongsTo(Ride::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('unread_count', 'last_read_at');
    }
}
