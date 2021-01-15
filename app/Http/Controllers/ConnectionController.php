<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Handlers\TokenHandler as TokenHandler;
use App\Handlers\EndpointHandler as EndpointHandler;
use App\Handlers\RealmHandler as RealmHandler;
use App\Handlers\AuctionLiveHandler as AuctionLiveHandler;
use App\Handlers\ItemHandler as ItemHandler;
use App\Handlers\ProfessionHandler as ProfessionHandler;
use Exception as GlobalException;
use Illuminate\Support\Facades\Cache;

class ConnectionController extends Controller
{
    public $selectedRealm = '';


    public function __construct()
    {
        $this->selectedRealm = RealmHandler::getRealmBySlug('dun-modr');
        $this->token = TokenHandler::getActiveTokenValueFromDB();
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        ini_set("xdebug.var_display_max_children", '-1');
        ini_set("xdebug.var_display_max_data", '-1');
        ini_set("xdebug.var_display_max_depth", '-1');
    }

    /**
     * [saveConnectedRealmApiData description]
     *
     * @return  [type]  [return description]
     */
    public function saveConnectedRealmApiData(){
        $data = $this->getConnectedRealmApiData();
        $realm_handler = new RealmHandler;
        $realm_handler->storeRealm($data);
    }

    /**
     * [getConnectedRealmApiData description]
     *
     * @return  [json]  [return description]
     */
    public function getConnectedRealmApiData()
    {
        $endpoint_handler = new EndpointHandler;
        $data = $endpoint_handler->connectedRealmApiCurl();
        if(empty($data)){
            $endpoint_handler->refreshToken();
            $data = $endpoint_handler->connectedRealmApiCurl();
        }
        return $data;
    }

    /**
     * [getAndSaveConnectedRealmApiName description]
     *
     * @return  [type]  [return description]
     */
    public function getAndSaveConnectedRealmApiName(){
        $endpoint_handler = new EndpointHandler;
        $realm_handler = new RealmHandler;

        $all_realms = RealmHandler::getAllRealms();
        foreach($all_realms as $key => $realm){
            $data = $endpoint_handler->genericBlizzardConnection( $realm->url);
            if(empty($data['body'])){
                $endpoint_handler->refreshToken();
                $data = $endpoint_handler->genericBlizzardConnection($realm->url);
            }
            $realm_concatenated_slug = $realm_handler->getConcatenatedRealmsName($data['body']);
            $realm_handler->saveRealmName($realm->blizzard_id, $realm_concatenated_slug);
        }
    }

    /**
     * [getAndSaveConnectedRealmAuctionHouseApiData description]
     *
     * @return  [integer]  [devuelve numero de subastas]
     */
    public function getAndSaveConnectedRealmAuctionHouseApiData(){
        $url = 'https://eu.api.blizzard.com/data/wow/connected-realm/'.$this->selectedRealm.'/auctions?namespace=dynamic-eu';

        $endpoint_handler = new EndpointHandler;
        $auctionlive_handler = new AuctionLiveHandler;

        $data = $endpoint_handler->genericBlizzardConnection($url);
        if(empty($data['body'])){
            $endpoint_handler->refreshToken();
            $data = $endpoint_handler->genericBlizzardConnection($url);
        }
        $auction_last_change_date = $auctionlive_handler->getLastChangeDate($data['header']);
        $last_auction_date_in_db = $auctionlive_handler->getLastAuctionDate();

        if($last_auction_date_in_db != $auction_last_change_date){
            $data = json_decode($data['body']);

            $auctions_array = $auctionlive_handler->prepareAuctionData($data);
            $auctions_array = $auctionlive_handler->adaptAuctionArrayToInsert($auctions_array, $auction_last_change_date);
            $auctionlive_handler->storeAuctionLiveBatch($auctions_array);
            $item_count = count($auctions_array) ?? 0;

            unset($auctions_array);
        }

        return $item_count;
    }

    /**
     * [getAndSaveItemData description]
     *
     * @return  [bool]  [devuelve true]
     */
    public function getAndSaveItemData(){
        $endpoint_handler = new EndpointHandler;
        $item_handler = new ItemHandler;
        $items_id = ItemHandler::getItemsIdFromAuctionsNotInsideItems();
        $iteration_through_items = $item_handler->iterateItemsGetSaveData($items_id);

        return $iteration_through_items;
    }

    public function getItemApiData($item_id){

        $endpoint_handler = new EndpointHandler;
        $data = $endpoint_handler->itemApiCurl($item_id);
        if(empty($data)){
            $endpoint_handler->refreshToken();
            $data = $endpoint_handler->itemApiCurl($item_id);
        }

        return $data;
    }

    public function getProfessions(){

        $profession_handler = new ProfessionHandler;
        $auctionlive_handler = new AuctionLiveHandler;
        $endpoint_handler = new EndpointHandler;
        $item_handler = new ItemHandler;

        try {
            $professions = $profession_handler->getCraftingProfessions('skinning');
            $cheap_recipes = [];
            foreach($professions as $index => $profession_url){
                $profession_data = Cache::store('file')->get($profession_url) ?? $endpoint_handler->genericBlizzardConnection($profession_url);
                Cache::store('file')->put($profession_url, $profession_data, 3600); // 60 Minutes
                //$profession_data = $endpoint_handler->genericBlizzardConnection($profession_url);
                if(empty($profession_data['body'])){
                    $endpoint_handler->refreshToken();
                    $profession_data = $endpoint_handler->genericBlizzardConnection($profession_url);
                    Cache::store('file')->put($profession_url, $profession_data, 3600); // 60 Minutes
                }
                $profession_tiers = json_decode($profession_data['body'])->skill_tiers;
                foreach($profession_tiers as $tier){
                    $tier_name = $tier->name->es_ES;
                    //$tier_data = $endpoint_handler->genericBlizzardConnection($tier->key->href);
                    $tier_data = Cache::store('file')->get($tier->key->href) ?? $endpoint_handler->genericBlizzardConnection($tier->key->href);
                    Cache::store('file')->put($tier->key->href, $tier_data, 3600);
                    if(empty($tier_data['body'])){
                        $endpoint_handler->refreshToken();
                        $tier_data = $endpoint_handler->genericBlizzardConnection($tier->key->href);
                        Cache::store('file')->put($tier->key->href, $tier_data, 3600);
                    }
                    $tier_categories = json_decode($tier_data['body'])->categories;
                    foreach($tier_categories as $category){
                        $category_name = $category->name->es_ES;
                        $category_recipes = $category->recipes;
                        echo '<pre>'.$tier_name.':'.$category_name.':'.count($category_recipes).'</pre>';
                        foreach($category_recipes as $recipe){
                            set_time_limit(20);
                            $recipe_name = $recipe->name->es_ES;
                            $recipe_id = $recipe->id;
                            //$recipe_data = $endpoint_handler->genericBlizzardConnection($recipe->key->href);
                            $recipe_data = Cache::store('file')->get($recipe->key->href) ?? $endpoint_handler->genericBlizzardConnection($recipe->key->href);
                            Cache::store('file')->put($recipe->key->href, $recipe_data, 3600);
                            if(empty($recipe_data['body'])){
                                $endpoint_handler->refreshToken();
                                $recipe_data = $endpoint_handler->genericBlizzardConnection($recipe->key->href);
                                Cache::store('file')->put($recipe->key->href, $recipe_data, 3600);
                            }
                            $decoded_recipe_data = json_decode($recipe_data['body']);
                            $crafted_item_id = $decoded_recipe_data->crafted_item->id ?? ($decoded_recipe_data->alliance_crafted_item->id ?? $decoded_recipe_data->id);
                            $crafted_item_vendor_price = $item_handler->getItemVendorPrice($crafted_item_id);

                            if($crafted_item_vendor_price == 0){
                                $item_handler->getItemAndSaveData($crafted_item_id);
                            }
                            if(!isset($decoded_recipe_data->reagents) || $crafted_item_vendor_price == 0){
                                continue;
                            }
                            $recipe_reagents = $decoded_recipe_data->reagents;
                            $crafting_price = 0;
                            $avoid = FALSE;

                            foreach($recipe_reagents as $reagent){
                                $reagent_quantity = $reagent->quantity;
                                $reagent_id = $reagent->reagent->id;
                                $reagent_name = $reagent->reagent->name->es_ES;
                                $item_price = $auctionlive_handler->getItemPriceFromLastAuctionDate($reagent_id);
                                if($item_price != 0){
                                    $crafting_price += ($reagent_quantity * $item_price);
                                    $reagents_list[$recipe_name][] =  $reagent_name."[$reagent_quantity:$item_price]";
                                }else{
                                    unset($cheap_recipes[$index][$tier_name][$recipe_name]);
                                    unset($reagents_list[$recipe_name]);
                                    $avoid = TRUE;
                                }
                            }
                            if(!$avoid  && ($crafted_item_vendor_price - $crafting_price)> 0){
                                $cheap_recipes[$index][$tier_name][$recipe_name]['price'] =  $crafted_item_vendor_price - $crafting_price;
                                $cheap_recipes[$index][$tier_name][$recipe_name]['reagents'] = $reagents_list[$recipe_name];
                            }
                            unset($reagents_list[$recipe_name]);
                        }
                        // if(isset($cheap_recipes[$index][$tier_name]) && is_array($cheap_recipes[$index][$tier_name])){
                        //     array_multisort($cheap_recipes[$index][$tier_name]['price'],SORT_DESC, SORT_NUMERIC);
                        // }
                    }
                }
            }
        } catch (\Throwable $th) {
            $decoded_recipe_data;
        }
        echo '<pre>';
        var_dump($cheap_recipes);
        echo '</pre>';
        return TRUE;
    }

}
