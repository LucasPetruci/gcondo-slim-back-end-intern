<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'max_people',
        'square_meters',
        'condominium_id',
    ];

    public function condominium()
    {
        return $this->belongsTo(Condominium::class);
    }
}
