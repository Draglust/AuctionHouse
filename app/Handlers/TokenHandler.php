<?php

namespace App\Handlers;

use App\Models\Token;
use Exception as GlobalException;

class TokenHandler
{
    public static function deactivateTokens(){
        $token = Token::where('active', 1)->first();
        $token->active = 0;
        $token->save();
    }

    public static function getActiveTokenValueFromDB(){
        $token = Token::where('active', 1)->first();

        if(isset($token->value)){
            return $token->value;
        }

        return false;
    }

    public function storeToken($json_data){
        $json_data = json_decode($json_data);
        $token = new Token;
        $token->value = $json_data->access_token;
        $token->expire = $json_data->expires_in;
        $token->active = 1;

        $token->save();
    }

    public function tokenExtraction($response)
    {
        $decoded_response = json_decode($response);
        $response = FALSE;
        if(!isset($decoded_response->error)){
            $response = $decoded_response->access_token;
        }
        return $response;
    }

    public function tokenJsonRetrieval($client_id, $request_secret)
    {

        try{
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, 'https://us.battle.net/oauth/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
            curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $request_secret);

            $headers = array();
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
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
            return $exception;
        }
    }
}
