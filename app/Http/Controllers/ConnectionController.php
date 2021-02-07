<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Handlers\TokenHandler as TokenHandler;
use App\Handlers\EndpointHandler as EndpointHandler;
use App\Handlers\RealmHandler as RealmHandler;
use App\Handlers\AuctionLiveHandler as AuctionLiveHandler;
use App\Handlers\ItemHandler as ItemHandler;
use App\Handlers\ProfessionHandler as ProfessionHandler;
use App\Handlers\RecipeHandler as RecipeHandler;
use App\Handlers\ReagentHandler as ReagentHandler;
use App\Handlers\WowheadHandler as WowheadHandler;
use Exception as GlobalException;
use stdClass;

class ConnectionController extends Controller
{
    public $selectedRealm = '';


    public function __construct()
    {
        $this->selectedRealm = RealmHandler::getRealmBySlug('dun-modr');
        $this->token = TokenHandler::getActiveTokenValueFromDB();
        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);
        ini_set("xdebug.var_display_max_children", '-1');
        ini_set("xdebug.var_display_max_data", '-1');
        ini_set("xdebug.var_display_max_depth", '-1');
        ini_set("xdebug.max_nesting_level", '-1');
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

    public function getProfitRecipes(){

        $auctionlive_handler = new AuctionLiveHandler;
        $item_handler = new ItemHandler;
        $recipe_handler = new RecipeHandler;
        $reagent_handler = new ReagentHandler;

        /**
         * CAMBIAR POR COMPLETO LA FUNCION USANDO LAS RECETAS EN BD
         */
        try {
            $recipe_list = $recipe_handler->getAllRecipes();

            foreach($recipe_list as $recipe){
                set_time_limit(20);
                $avoid = FALSE;
                $crafting_price = 0;

                $reagents = $reagent_handler->getReagentByRecipeId($recipe['id']);
                $crafted_item_vendor_price = $item_handler->getItemVendorPriceOrSearchIt($recipe['crafted_item_id']);
                foreach ($reagents as $index => $reagent) {
                    $reagent_quantity = $reagent['quantity'];
                    $reagent_id = $reagent['item_id'];
                    $item_price = $auctionlive_handler->getItemPriceFromLastAuctionDate($reagent_id);
                    if($item_price == 0){
                        $item_price = $item_handler->getItemVendorPriceOrSearchIt($reagent_id);
                    }
                    if($item_price != 0){
                        $crafting_price += ($reagent_quantity * $item_price);
                        //$reagents_list[$recipe['recipe_name']][] =  $recipe['recipe_name']."[$reagent_quantity:$item_price]";
                    }else{
                        //unset($cheap_recipes[$recipe['profession']][$recipe['tier_name']][$recipe['recipe_name']]);
                        //unset($reagents_list[$recipe['recipe_name']]);
                        $avoid = TRUE;
                    }
                }
                if(!$avoid  && ($crafted_item_vendor_price - $crafting_price)> 0 && $crafting_price != 0){
                    $cheap_recipes[$recipe['profession']][$recipe['tier_name']][$recipe['recipe_name']]['price'] =  $crafted_item_vendor_price - $crafting_price;
                    //$cheap_recipes[$recipe['profession']][$recipe['recipe_name']]['reagents'] = $reagents_list[$recipe['recipe_name']];
                }
                //unset($reagents_list[$recipe['recipe_name']]);

            }
            // if(isset($cheap_recipes[$index][$tier_name]) && is_array($cheap_recipes[$index][$tier_name])){
            //     array_multisort($cheap_recipes[$index][$tier_name]['price'],SORT_DESC, SORT_NUMERIC);
            // }



        } catch (GlobalException $e) {
            dd($e);
            dd($recipe);
        }
        echo '<pre>';
        var_dump($cheap_recipes);
        echo '</pre>';
        return TRUE;
    }

    public function getRecipes(){

        $profession_handler = new ProfessionHandler;
        $recipe_handler = new RecipeHandler;
        $reagent_handler = new ReagentHandler;

        try {
            $professions = $profession_handler->getCraftingProfessions();
            foreach($professions as $profession_name => $profession_url){
                $profession_data = $profession_handler->getProfessionDataGivenUrl($profession_url);
                $profession_tiers = json_decode($profession_data['body'])->skill_tiers;
                foreach($profession_tiers as $tier){
                    $tier_name = $tier->name->es_ES;
                    $tier_data = $profession_handler->getTierDataGivenUrl($tier->key->href);
                    $tier_categories = json_decode($tier_data['body'])->categories;
                    foreach($tier_categories as $category){
                        $category_name = $category->name->es_ES;
                        $category_recipes = $category->recipes;
                        $recipes = $recipe_handler->getNotInsertedRecipes($category_recipes);

                        foreach($recipes as $recipe){
                            set_time_limit(20);

                            $recipe_data = $recipe_handler->getRecipeDataGivenUrl($recipe->key->href);
                            $decoded_recipe_data = json_decode($recipe_data['body']);

                            if(!isset($decoded_recipe_data->crafted_item->id)){
                                continue;
                            }
                            $recipe_data_to_save = new stdClass();
                            $recipe_data_to_save->id = $recipe->id;
                            $recipe_data_to_save->profession = $profession_name;
                            $recipe_data_to_save->tier_name = $tier_name;
                            $recipe_data_to_save->category_name = $category_name;
                            $recipe_data_to_save->recipe_name = $recipe->name->es_ES;
                            $recipe_data_to_save->recipe_url = $recipe->key->href;
                            $recipe_data_to_save->crafted_item_id = $decoded_recipe_data->crafted_item->id;
                            $recipe_handler->saveRecipeData($recipe_data_to_save);

                            if(!isset($decoded_recipe_data->reagents)){
                                continue;
                            }
                            $recipe_reagents = $decoded_recipe_data->reagents;

                            foreach($recipe_reagents as $reagent){
                                $reagent_data = new stdClass();
                                $reagent_data->item_id = $reagent->reagent->id;
                                $reagent_data->recipe_id = $recipe->id;
                                $reagent_data->quantity = $reagent->quantity;
                                $reagent_handler->saveReagentData($reagent_data);
                            }

                        }

                    }
                }
            }
        } catch (GlobalException $e) {
            echo __FUNCTION__;
            dd($decoded_recipe_data);
        }
        // echo '<pre>';
        // var_dump($cheap_recipes);
        // echo '</pre>';
        return true;
    }

    public function getDropPoints($item_id = NULL){
        //id de ejemplo 172053
        $parsed_array = [];
        $wowhead_handler = new WowheadHandler;
        $web_data = $wowhead_handler->getWebData($item_id, 'item');
        $dropped_by_data = $wowhead_handler->getCleanedDroppedByData($web_data);
        foreach($dropped_by_data as $dropping_npc){
            $name = $dropping_npc->name;
            $id = $dropping_npc->id;
            $npc_web_data = $wowhead_handler->getWebData($dropping_npc->id, 'npc', $dropping_npc->name);
            $cleaned_dropped_by_data = $wowhead_handler->getCleanedNpcData($npc_web_data);
            if($cleaned_dropped_by_data == NULL){
                continue;
            }
            $parsed_data = $wowhead_handler->parseData($cleaned_dropped_by_data);
            $parsed_array[$parsed_data['index']]['values']['uiMapId'] = $parsed_data['values']['uiMapId'];
            $parsed_array[$parsed_data['index']]['values']['uiMapName'] = $parsed_data['values']['uiMapName'];
            $parsed_array[$parsed_data['index']]['values']['count'] = isset($parsed_array[$parsed_data['index']]['values']['count'])
                                                                        ? $parsed_array[$parsed_data['index']]['values']['count'] + $parsed_data['values']['count']
                                                                        : $parsed_data['values']['count'];
            $parsed_array[$parsed_data['index']]['values']['coords'] = isset($parsed_array[$parsed_data['index']]['values']['coords'])
                                                                        ? $wowhead_handler->appendCoords($parsed_array[$parsed_data['index']]['values']['coords'], $parsed_data['values']['coords'])
                                                                        : $parsed_data['values']['coords'];
            unset($parsed_data);
        }

        return view("maps")->with('maps',$parsed_array);
    }

    public function getSkinningPoints($item_id = NULL){
        //id de ejemplo 172053
        $parsed_array = [];
        $wowhead_handler = new WowheadHandler;
        $web_data = $wowhead_handler->getWebData($item_id, 'item');
        $skinned_data = $wowhead_handler->getCleanedSkinningByData($web_data);
        foreach($skinned_data as $skinned_npc){
            $name = $skinned_npc->name;
            $id = $skinned_npc->id;
            $classification = $skinned_npc->classification;
            $reaction = $skinned_npc->react[0];
            $npc_web_data = $wowhead_handler->getWebData($skinned_npc->id, 'npc', $skinned_npc->name);
            $cleaned_skinned_data = $wowhead_handler->getCleanedNpcData($npc_web_data);
            if($cleaned_skinned_data == NULL){
                continue;
            }
            $parsed_data = $wowhead_handler->parseData($cleaned_skinned_data);
            $parsed_array[$parsed_data['index']]['values']['uiMapId'] = $parsed_data['values']['uiMapId'];
            $parsed_array[$parsed_data['index']]['values']['uiMapName'] = $parsed_data['values']['uiMapName'];
            $parsed_array[$parsed_data['index']]['values']['count'] = isset($parsed_array[$parsed_data['index']]['values']['count'])
                                                                        ? $parsed_array[$parsed_data['index']]['values']['count'] + $parsed_data['values']['count']
                                                                        : $parsed_data['values']['count'];
            if($classification == 0){
                if($reaction == -1){
                    $parsed_array[$parsed_data['index']]['values']['coords_normal_aggresive'] = isset($parsed_array[$parsed_data['index']]['values']['coords_normal_aggresive'])
                                                                                ? $wowhead_handler->appendCoords($parsed_array[$parsed_data['index']]['values']['coords_normal_aggresive'], $parsed_data['values']['coords'])
                                                                                : $parsed_data['values']['coords'];
                }
                else{
                    $parsed_array[$parsed_data['index']]['values']['coords_normal'] = isset($parsed_array[$parsed_data['index']]['values']['coords_normal'])
                                                                                ? $wowhead_handler->appendCoords($parsed_array[$parsed_data['index']]['values']['coords_normal'], $parsed_data['values']['coords'])
                                                                                : $parsed_data['values']['coords'];
                }
            }else{
                $parsed_array[$parsed_data['index']]['values']['coords_elite'] = isset($parsed_array[$parsed_data['index']]['values']['coords_elite'])
                                                                        ? $wowhead_handler->appendCoords($parsed_array[$parsed_data['index']]['values']['coords_elite'], $parsed_data['values']['coords'])
                                                                        : $parsed_data['values']['coords'];
            }
            unset($parsed_data);
        }

        return view("maps")->with('maps',$parsed_array);
    }


}

