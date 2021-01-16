<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $table = 'recipe';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
}
