<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Images extends Model
{
    protected $table = 'images';

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
//    public function getImageUrlAttribute($value)
//    {
//        return asset('storage/' . $value);
//    }
    public function menus()
    {
        return $this->belongsTo(Menus::class, 'menu_id');
    }
}
