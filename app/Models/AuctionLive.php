<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class AuctionLive extends Model
{
    protected $table = 'auction_live';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;
}
