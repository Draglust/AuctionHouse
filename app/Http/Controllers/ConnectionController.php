<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Handlers\TokenHandler as TokenHandler;
use App\Handlers\EndpointHandler as EndpointHandler;
use App\Handlers\RealmHandler as RealmHandler;
use App\Handlers\AuctionLiveHandler as AuctionLiveHandler;
use App\Handlers\ItemHandler as ItemHandler;
use Exception as GlobalException;

class ConnectionController extends Controller
{
    public $selectedRealm = '';


    public function __construct()
    {
        $this->selectedRealm = RealmHandler::getRealmBySlug('uldum');
        $this->token = TokenHandler::getActiveTokenValueFromDB();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function saveConnectedRealmApiData(){
        $data = $this->getConnectedRealmApiData();
        $realm_handler = new RealmHandler;
        $realm_handler->storeRealm($data);
    }

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

        $data = json_decode($data['body']);

        $auctions_array = $auctionlive_handler->prepareAuctionData($data);

        $auctions_array = $auctionlive_handler->adaptAuctionArrayToInsert($auctions_array, $auction_last_change_date);

        $auctionlive_handler->storeAuctionLiveBatch($auctions_array);
        unset($auctions_array);
        echo 'Fin';
        return true;
    }

    public function getItemApiData($item_id){
        $url = 'https://eu.api.blizzard.com/data/wow/connected-realm/'.$this->selectedRealm.'/auctions?namespace=dynamic-eu';

        $endpoint_handler = new EndpointHandler;
        $data = $endpoint_handler->itemApiCurl($item_id);
        if(empty($data)){
            $endpoint_handler->refreshToken();
            $data = $endpoint_handler->itemApiCurl($item_id);
        }

        return $data;
    }

}
