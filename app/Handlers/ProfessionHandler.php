<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Handlers\EndpointHandler as EndpointHandler;
use Illuminate\Support\Facades\Cache;

class ProfessionHandler
{
    public static function getCraftingProfessions($profession_name = NULL){
        //$professions['enchanting'] = 'https://eu.api.blizzard.com/data/wow/profession/333?namespace=static-9.0.2_36532-eu';
        $professions['tailoring'] = 'https://eu.api.blizzard.com/data/wow/profession/197?namespace=static-9.0.2_36532-eu';
        $professions['blacksmith'] = 'https://eu.api.blizzard.com/data/wow/profession/164?namespace=static-9.0.2_36532-eu';
        $professions['skinning'] = 'https://eu.api.blizzard.com/data/wow/profession/165?namespace=static-9.0.2_36532-eu';
        $professions['alchemy'] = 'https://eu.api.blizzard.com/data/wow/profession/171?namespace=static-9.0.2_36532-eu';
        $professions['cooking'] = 'https://eu.api.blizzard.com/data/wow/profession/185?namespace=static-9.0.2_36532-eu';
        $professions['engineering'] = 'https://eu.api.blizzard.com/data/wow/profession/202?namespace=static-9.0.2_36532-eu';
        $professions['jewelcrafting'] = 'https://eu.api.blizzard.com/data/wow/profession/755?namespace=static-9.0.2_36532-eu';
        $professions['inscription'] = 'https://eu.api.blizzard.com/data/wow/profession/773?namespace=static-9.0.2_36532-eu';

        return isset($professions[$profession_name]) ? array($profession_name => $professions[$profession_name]) : $professions ;
    }

    public function getProfessionDataGivenUrl($profession_url){
        $endpoint_handler = new EndpointHandler;

        $profession_data = Cache::store('file')->get($profession_url) ?? $endpoint_handler->genericBlizzardConnection($profession_url);
        if(empty($profession_data['body'])){
            $endpoint_handler->refreshToken();
            $profession_data = $endpoint_handler->genericBlizzardConnection($profession_url);
        }
        Cache::store('file')->put($profession_url, $profession_data, 3600);

        return $profession_data;
    }

    public function getTierDataGivenUrl($tier_url){
        $endpoint_handler = new EndpointHandler;

        $tier_data = Cache::store('file')->get($tier_url) ?? $endpoint_handler->genericBlizzardConnection($tier_url);
        if(empty($tier_data['body'])){
            $endpoint_handler->refreshToken();
            $tier_data = $endpoint_handler->genericBlizzardConnection($tier_url);
        }
        Cache::store('file')->put($tier_url, $tier_data, 3600);

        return $tier_data;
    }

}
