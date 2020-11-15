<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Handlers\EndpointHandler as EndpointHandler;
use App\models\RawItem;

class RawitemHandler
{
    public static function storeRawItem($json_data){
        $raw_item = new RawItem;
        $raw_item->value = $json_data;

        $raw_item->save();
    }
}
