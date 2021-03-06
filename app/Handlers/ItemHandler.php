<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Item;
use App\Models\AuctionLive;
use Illuminate\Support\Facades\Cache;

class ItemHandler
{
    public static function getClasses(){
        $classes = Item::select('item_class','item_class_id')->distinct()->get();

        return $classes;
    }
    public static function getItemsIdFromAuctionsNotInsideItems(){
        $items_id = Item::pluck('id')->unique();
        $items_id_in_auctions = AuctionLive::whereNotIn('item_id',$items_id->all())->select('item_id')->distinct()->get();
        return $items_id_in_auctions;
    }

    public function deleteItemAndRelatedAuction($item_id){
        Item::where('id', '=', $item_id)->delete();
        Auctionlive::where('item_id', '=', $item_id)->delete();
    }

    public function iterateItemsGetSaveData($items_id){
        $endpoint_handler = new EndpointHandler;
        $item_handler = new ItemHandler;
        foreach($items_id as $item_id){
            set_time_limit(40);
            $item_data = $endpoint_handler->itemApiCurl($item_id->item_id);
            if(isset(json_decode($item_data)->code) && json_decode($item_data)->code == 404 && json_decode($item_data)->detail == 'Not Found'){
                $item_handler->deleteItemAndRelatedAuction($item_id->item_id);
                unset($item_data);
                continue;
            }
            if(empty($item_data)){
                $endpoint_handler->refreshToken();
                $item_data = $endpoint_handler->itemApiCurl($item_id->item_id);
            }
            $item_data = json_decode($item_data);
            $item_handler->saveItemData($item_data);
            unset($item_data);
        }

        return true;
    }
    public function getItemAndSaveData($item_id){
        $endpoint_handler = new EndpointHandler;
        $item_handler = new ItemHandler;
        set_time_limit(40);
        //$item_data = $endpoint_handler->itemApiCurl($item_id);
        $item_data = Cache::store('file')->get('item:'.$item_id) ?? $endpoint_handler->itemApiCurl($item_id);
        Cache::store('file')->put('item:'.$item_id, $item_data, 3600);
        if(isset(json_decode($item_data)->code) && json_decode($item_data)->code == 404 && json_decode($item_data)->detail == 'Not Found'){
            $item_handler->deleteItemAndRelatedAuction($item_id);
            unset($item_data);
            return false;
        }
        if(empty($item_data)){
            $endpoint_handler->refreshToken();
            $item_data = $endpoint_handler->itemApiCurl($item_id);
        }
        $item_data = json_decode($item_data);
        $item_handler->saveItemData($item_data);
        unset($item_data);

        return true;
    }

    public function saveItemData($data){
        try{
            $item_already_inserted = Item::where('id', '=', $data->id)->get();

            if($item_already_inserted->count() == 0){
                $item = new Item;
                $item->id = $data->id;
                $item->name = $data->name->es_ES ?? $data->name->es_MX;
                $item->quality = $data->quality->type;
                $item->level = $data->level;
                $item->required_level = $data->required_level;
                $item->sell_price = $data->preview_item->sell_price->value ?? $data->sell_price;
                $item->purchase_price = $data->purchase_price;
                $item->item_class = $data->item_class->name->es_ES ?? $data->item_class->name->es_MX;
                $item->item_class_id = $data->item_class->id;
                $item->item_subclass= $data->item_subclass->name->es_ES ?? $data->item_subclass->name->es_MX;
                $item->item_subclass_id = $data->item_subclass->id;
                $item->inventory_type = $data->inventory_type->type;
                $item->save();

                unset($item);
            }
            unset($item_already_inserted);

            return true;
        }
        catch(GlobalException $e){
            dd($data);
        }
        return false;
    }

    public function getItemVendorPriceOrSearchIt($item_id){
        $item  = Item::where('id', '=', $item_id)->get();

        if(!isset($item->first()->sell_price)){
            $this->getItemAndSaveData($item_id);
            $item  = Item::where('id', '=', $item_id)->get();
        }

        return $item->first()->sell_price ?? 0;
    }

    public function getItemVendorPrice($item_id){
        $item  = Item::where('id', '=', $item_id)->get();

        return $item->first()->sell_price ?? 0;
    }
}
