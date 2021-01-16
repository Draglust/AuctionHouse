<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Reagent extends Model
{
    protected $table = 'reagent';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = false;
}
