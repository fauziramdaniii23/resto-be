<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menus extends Model
{
    protected $table = 'menus';

    protected $fillable = [
        // tambahkan kolom yang dapat diisi, contoh:
        // 'name', 'description', 'price'
    ];
}
