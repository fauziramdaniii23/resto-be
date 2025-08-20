<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menus extends Model
{
    protected $table = 'menus';
    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at'];
    public function categories()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }
    public function images()
    {
        return $this->hasMany(Images::class, 'menu_id');
    }
}
