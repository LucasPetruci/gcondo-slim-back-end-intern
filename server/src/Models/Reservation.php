<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'name',
        'unit_id',
        'people_quantity',
        'date'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
