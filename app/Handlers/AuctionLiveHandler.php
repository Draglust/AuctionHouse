<?php

namespace App\Handlers;

use App\Models\AuctionLive;
use Exception as GlobalException;
use Illuminate\Support\Carbon;

class AuctionLiveHandler
{
    public function OrderArrayAndRemoveLastElement(array $prices_array, $last_price){
        sort($prices_array, SORT_NUMERIC);
        array_pop($prices_array);
        array_push($prices_array, $last_price);

        return $prices_array;
    }

    public function storeAuctionLive($data, $id){
        $auction_live = new AuctionLive;
        $auction_live->item_id = $id;
        $auction_live->unit_price = $data['final_price'];
        $auction_live->quantity = $data['total_quantity'];
        $auction_live->date = $data['date'];

        $auction_live->save();
    }

    public static function getLastAuctionDate(){
        $auction = AuctionLive::orderBy('date', 'desc')->first();

        return $auction->date;
    }

    public function storeAuctionLiveBatch($data){
        foreach(array_chunk($data, 2000) as $small_batch){
            AuctionLive::insert($small_batch);
        }
    }

    public function prepareAuctionData($data){
        foreach($data->auctions as $index => $raw_auction){
            try{
                set_time_limit(20);
                if(isset($raw_auction->buyout)){
                    $raw_auction->unit_price = $raw_auction->buyout;
                }
                if(!isset($raw_auction->unit_price)){
                    continue;
                }
                if(!isset($auctions_array[$raw_auction->item->id]['quantity'])){
                    $auctions_array[$raw_auction->item->id]['quantity'] = 0;
                }
                $auctions_array[$raw_auction->item->id]['quantity'] += $raw_auction->quantity;

                if(!isset($auctions_array[$raw_auction->item->id]['prices'])){
                    $auctions_array[$raw_auction->item->id]['prices'][] = $raw_auction->unit_price;
                }
                else{
                    $auctions_array[$raw_auction->item->id]['prices'] = $this->getTenMinimumPrices($auctions_array[$raw_auction->item->id]['prices'], $raw_auction->unit_price);
                }

                unset($data->auctions[$index]);
            }
            catch(GlobalException $e ){
                dd($raw_auction);
            }
            //RawitemHandler::storeRawItem(json_encode($raw_auction));
        }

        return $auctions_array;
    }

    public function adaptAuctionArrayToInsert($auctions_array, $auction_last_change_date){
        foreach($auctions_array as $item_id => $auction_element){
            set_time_limit(20);
            $auctions_array[$item_id]['item_id'] = $item_id;
            $auctions_array[$item_id]['date'] = $auction_last_change_date;
            $auctions_array[$item_id]['unit_price'] = round(array_sum($auction_element['prices']) / count($auction_element['prices']),0);
            $auctions_array[$item_id]['created_at'] = Carbon::now()->toDateTimeString();
            $auctions_array[$item_id]['updated_at'] = Carbon::now()->toDateTimeString();
            unset($auctions_array[$item_id]['prices']);
        }

        return $auctions_array;
    }

    public function getTenMinimumPrices($array, $unit_price){
        if(count($array) < 10 ){
            $array[] = $unit_price;
        }
        else{
            if(max($array)> $unit_price){
                $array = $this->OrderArrayAndRemoveLastElement($array, $unit_price);
            }
        }

        return $array;
    }

    public function getLastChangeDate($header){
        preg_match('/Last-Modified: (.*){3},(.*)GMT/',$header, $coincidences);
        $date = date('Y-m-d H:i:s');
        if(isset($coincidences[2])){
            $date = date('Y-m-d H:i:s', strtotime('+1 hour '.trim($coincidences[2])));
        }
        return $date;
    }
}
