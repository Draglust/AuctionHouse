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
    public $ClientID = '14bad3ddde5e455d84d778ae945c6eca';
    public $requestSecret = 'WKr9xUFvzIQ9ZSJ134WEjMQSZI8nFwuF';
    public $token = false;
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
        if(!$this->token){
            $this->getCredentials();
        }
        $endpoint_handler = new EndpointHandler;
        $data = $endpoint_handler->connectedRealmApiCurl($this->token);
        if(empty($data)){
            $this->refreshToken();
            $data = $endpoint_handler->connectedRealmApiCurl($this->token);
        }
        return $data;
    }

    public function getConnectedRealmApiName(){
        $endpoint_handler = new EndpointHandler;
        $realm_handler = new RealmHandler;
        if(!$this->token){
            $this->getCredentials();
        }
        $all_realms = RealmHandler::getAllRealms();
        foreach($all_realms as $key => $realm){
            $data = $endpoint_handler->genericBlizzardConnection($this->token, $realm->url);
            if(empty($data['body'])){
                $this->refreshToken();
                $data = $endpoint_handler->genericBlizzardConnection($this->token, $realm->url);
            }
            $realm_concatenated_slug = $realm_handler->getConcatenatedRealmsName($data['body']);
            $realm_handler->saveRealmName($realm->blizzard_id, $realm_concatenated_slug);
        }
    }

    public function getConnectedRealmAuctionHouseApiData(){
        $url = 'https://eu.api.blizzard.com/data/wow/connected-realm/'.$this->selectedRealm.'/auctions?namespace=dynamic-eu';

        $endpoint_handler = new EndpointHandler;
        $auctionlive_handler = new AuctionLiveHandler;
        $data = $endpoint_handler->genericBlizzardConnection($this->token, $url);
        if(empty($data['body'])){
            $this->refreshToken();
            $data = $endpoint_handler->genericBlizzardConnection($this->token, $url);
        }
        $auction_last_change_date = $auctionlive_handler->getLastChangeDate($data['header']);

        $data = json_decode($data['body']);

        $auctions_array = $auctionlive_handler->prepareAuctionData($data);

        $auctions_array = $auctionlive_handler->adaptAuctionArrayToInsert($auctions_array, $auction_last_change_date);

        $auctionlive_handler->storeAuctionLiveBatch($auctions_array);
        unset($auctions_array);
        echo 'Fin';
        return false;
    }

    public function getCredentials()
    {
        $token = TokenHandler::getActiveTokenValueFromDB();
        if($token == null || $token == false){
            $token_handler = new TokenHandler;
            $response = $token_handler->tokenJsonRetrieval($this->ClientID, $this->requestSecret);
            $token_handler->storeToken($response);
            $token = $token_handler->tokenExtraction($response);
        }
        $this->token = $token;

        return $this->token;

    }

    public function refreshToken()
    {
        TokenHandler::deactivateTokens();
        $this->getCredentials();
    }


}
