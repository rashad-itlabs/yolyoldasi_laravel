<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecentSearch extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'from_city_id', 'to_city_id',
        'searched_date', 'seats', 'result_count', 'searched_at',
    ];

    protected $casts = [
        'searched_date' => 'date',
        'searched_at'   => 'datetime',
    ];

    public function fromCity()
    {
        return $this->belongsTo(City::class, 'from_city_id');
    }

    public function toCity()
    {
        return $this->belongsTo(City::class, 'to_city_id');
    }
}
