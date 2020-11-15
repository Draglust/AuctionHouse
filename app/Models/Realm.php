<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Realm extends Model
{
    protected $table = 'realm';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
