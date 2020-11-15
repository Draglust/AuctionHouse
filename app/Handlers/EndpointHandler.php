<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Realm;

class EndpointHandler
{
    public function connectedRealmApiCurl($token)
    {
        $redirect_uri = 'https://eu.api.blizzard.com/data/wow/connected-realm/index';

        try{
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $redirect_uri);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


            $headers = array();
            $headers[] = 'Authorization: Bearer '.$token;
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

    public function genericBlizzardConnection($token, $url)
    {

        try{
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


            $headers = array();
            $headers[] = 'Authorization: Bearer '.$token;

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
