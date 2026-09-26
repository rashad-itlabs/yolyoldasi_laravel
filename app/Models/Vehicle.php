<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'color',
        'plate',
        'year',
        'seats',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
