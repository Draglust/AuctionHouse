<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Item;
use App\Models\AuctionLive;

class ItemHandler
{
    public static function getItemsIdFromAuctionsNotInsideItems(){
        $items_id = Item::pluck('id')->unique();
        $items_id_in_auctions = AuctionLive::whereNotIn('item_id',$items_id->all())->select('item_id')->distinct()->get();
        return $items_id_in_auctions;
    }

    public function saveItemData($data){
        try{
            $item_already_inserted = Item::where('id', '=', $data->id)->get();
            if($data->id == 87450 || $data->id == 15347 ||  $data->id == 55413 ||  $data->id == 116567 ||  $data->id == 6591 || $data->id == 38781){
                echo 'entra '.$data->id.'| cuenta es '.$item_already_inserted->count();
            }
            if($item_already_inserted->count() == 0){
                $item = new Item;
                $item->id = $data->id;
                $item->name = $data->name->es_ES ?? $data->name->es_MX;
                $item->quality = $data->quality->type;
                $item->level = $data->level;
                $item->required_level = $data->required_level;
                $item->sell_price = $data->sell_price;
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
}
