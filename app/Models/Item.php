<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
}
