<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'booking_id', 'ride_id', 'author_id', 'target_id',
        'rating', 'author_was_driver', 'comment',
    ];

    protected $casts = ['author_was_driver' => 'boolean'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    /** Hədəfin SÜRÜCÜ kimi aldığı rəylər — müəllif sərnişin olub. */
    public function scopeAboutDriver($query, int $userId)
    {
        return $query->where('target_id', $userId)->where('author_was_driver', false);
    }

    /** Hədəfin SƏRNİŞİN kimi aldığı rəylər — müəllif sürücü olub. */
    public function scopeAboutPassenger($query, int $userId)
    {
        return $query->where('target_id', $userId)->where('author_was_driver', true);
    }
}
