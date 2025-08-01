<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $table = 'tables';

    protected $guarded = ['id'];
    public function reservations()
    {
        return $this->belongsToMany(Reservation::class);
    }

}
