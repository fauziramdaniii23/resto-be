<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categories extends Model
{
    protected $table = 'categories';

    protected $guarded = ['id'];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function menus()
    {
        return $this->hasMany(Menus::class, 'category_id');
    }
}
