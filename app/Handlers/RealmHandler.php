<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Realm;
use App\Handlers\EndpointHandler as EndpointHandler;

class RealmHandler
{
    public function storeRealm($json_data){
        $json_data = json_decode($json_data);

        foreach ($json_data->connected_realms as $key => $realm_url) {
            $existing_realm = Realm::where('url', '=', $realm_url->href)->first();
            if ($existing_realm === null) {
                $this->saveRealmGeneralData($realm_url->href);
            }

        }

    }

    public function saveRealmGeneralData($url){
        $realm = new Realm;
        $realm->url = $url;
        $realm->blizzard_id = $this->getRealmIdFromUrl($url);

        $realm->save();
    }

    public function getConcatenatedRealmsName($data)
    {
        $data = json_decode($data);
        $realm_concatenated_slug = '';
        foreach($data->realms as $index => $realm_data){
            $realm_concatenated_slug .= $realm_data->slug.'|';
        }
        $realm_concatenated_slug = rtrim($realm_concatenated_slug, '|');

        return $realm_concatenated_slug;
    }

    public static function getAllRealms(){
        $all_realms = Realm::where('name','=','null')->get();
        return $all_realms;
    }

    public static function getRealmBySlug($slug){
        $selected_realm = Realm::where('name','LIKE','%'.$slug.'%')->first();
        return $selected_realm->blizzard_id;
    }

    public function saveRealmName($blizzard_id, $name){
        $existing_realm = Realm::where('blizzard_id', '=', $blizzard_id)->first();
        $existing_realm->name = $name;
        $existing_realm->save();
    }

    public function getRealmIdFromUrl($realm_url){
        $blizzard_id = false;
        preg_match('([0-9]+)', $realm_url, $matches);
        if($matches){
            $blizzard_id = $matches[0];
        }
        return $blizzard_id;
    }
}
