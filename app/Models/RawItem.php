<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class RawItem extends Model
{
    protected $table = 'raw_item';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
