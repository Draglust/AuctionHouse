<?php

namespace App\Handlers;

use Exception as GlobalException;
use App\Models\Item;
use App\Models\AuctionLive;

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

}
