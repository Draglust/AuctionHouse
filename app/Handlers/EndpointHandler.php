<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Realm;

class EndpointHandler
{
    public $ClientID = '14bad3ddde5e455d84d778ae945c6eca';
    public $requestSecret = 'WKr9xUFvzIQ9ZSJ134WEjMQSZI8nFwuF';
    public $itemApiUrl = 'https://eu.api.blizzard.com/data/wow/item/';
    public $connectedRealmApiurl = 'https://eu.api.blizzard.com/data/wow/connected-realm/index';
    public $token = false;

    public function __construct()
    {
        if(empty($this->token)){
            $this->token = $this->getCredentials();
        }
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

    public function connectedRealmApiCurl()
    {
        $redirect_uri = $this->connectedRealmApiUrl;

        try{
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $redirect_uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


            $headers = array();
            $headers[] = 'Authorization: Bearer '.$this->token;
            $headers[] = 'Battlenet-Namespace: dynamic-eu';
            $headers[] = 'locale: es_ES';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);

            return $result;
        }
        catch(GlobalException $exception){
            //return back()->withError($exception->getMessage())->withInput();
            return $exception->getMessage();
        }
    }

    public function itemApiCurl($item_id)
    {
        $redirect_uri = $this->itemApiUrl. $item_id;

        try{
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $redirect_uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


            $headers = array();
            $headers[] = 'Authorization: Bearer '.$this->token;
            $headers[] = 'Battlenet-Namespace: static-eu';
            $headers[] = 'locale: es_ES';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);

            return $result;
        }
        catch(GlobalException $exception){
            //return back()->withError($exception->getMessage())->withInput();
            return $exception->getMessage();
        }
    }

    public function genericBlizzardConnection($url)
    {

        try{
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


            $headers = array();
            $headers[] = 'Authorization: Bearer '.$this->token;

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = trim(substr($result, 0, $header_size));
            $body = substr($result, $header_size);
            $response['header'] = $header;
            $response['body'] = $body;
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            curl_close($ch);

            return $response;
        }
        catch(GlobalException $exception){
            //return back()->withError($exception->getMessage())->withInput();
            return $exception->getMessage();
        }
    }
}
