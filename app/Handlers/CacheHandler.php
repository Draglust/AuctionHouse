<?php

namespace App\Handlers;

use App\Handlers\TokenHandler as TokenHandler;
use App\Handlers\EndpointHandler as EndpointHandler;
use App\Handlers\RealmHandler as RealmHandler;
use App\Handlers\AuctionLiveHandler as AuctionLiveHandler;
use App\Handlers\ItemHandler as ItemHandler;
use App\Handlers\ProfessionHandler as ProfessionHandler;
use App\Handlers\RecipeHandler as RecipeHandler;
use App\Handlers\ReagentHandler as ReagentHandler;
use App\Handlers\WowheadHandler as WowheadHandler;
use Illuminate\Support\Facades\Cache;
use Exception as GlobalException;

class CacheHandler
{
    public static function skinningItemWebCache($item_id){
        $wowhead_handler = new WowheadHandler;
        $web_data = Cache::store('file')->get('skinning:'.$item_id) ?? $wowhead_handler->getWebData($item_id, 'item');
        Cache::store('file')->put('skinning:'.$item_id, $web_data, 3600);

        return $web_data;
    }

    public static function npcWebCache($npc_id, $npc_name){
        $wowhead_handler = new WowheadHandler;
        $npc_web_data = Cache::store('file')->get('npc:'.$npc_id) ?? $wowhead_handler->getWebData($npc_id, 'npc', $npc_name);
            if(empty($npc_web_data)){
                $wowhead_handler->getWebData($npc_id, 'npc', $npc_name);
            }
            Cache::store('file')->put('npc:'.$npc_id, $npc_web_data, 3600);

        return $npc_web_data;
    }
}
