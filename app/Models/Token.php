<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $table = 'token';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
    protected $attributes = [
        'active' => 0,
        'expire' => -1,
    ];
}
