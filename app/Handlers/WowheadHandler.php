<?php

namespace App\Handlers;

use App\Models\Item;
use App\Helpers\TextTransformHelper;
use Exception as GlobalException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WowheadHandler
{
    protected $item_url = 'https://es.wowhead.com/item=';
    protected $npc_url = 'https://es.wowhead.com/npc=';

    public function getWebData($id = NULL, $type, $name = ''){
        try {
            $url = $this->selectUrlType($type).$id.'/'.strtolower(TextTransformHelper::customUrlEncode(str_replace(' ','-',$name)));
            $data = Http::get($url);
            //file_put_contents('C:\temp\file.txt',json_encode($data->headers()).'\r\n', FILE_APPEND);
            if($data->status() == 200){
                $body = $data->body();
            }

            return $body;
        } catch (GlobalException $th) {
            var_dump($url);
            dd($th);
            die();
        }

    }



    public function selectUrlType($type){
        switch ($type) {
            case 'item':
                $url = $this->item_url;
                break;
            case 'npc':
                $url = $this->npc_url;
                break;
            default:
                $url = $this->item_url;
                break;
        }
        return $url;
    }

    public function getCleanedDroppedByData($data){
        $dropped_by_data = TextTransformHelper::getDroppedByDataFromWebData($data);
        $cleaned_data = TextTransformHelper::cleanData($dropped_by_data);
        $decoded_data = $this->jsonDataToArray($cleaned_data);

        return $decoded_data;
    }

    public function getCleanedSkinningByData($data){
        $dropped_by_data = TextTransformHelper::getSkinnedDataFromWebData($data);
        $cleaned_data = TextTransformHelper::cleanData($dropped_by_data);
        $decoded_data = $this->jsonDataToArray($cleaned_data);

        return $decoded_data;
    }

    public function getCleanedNpcData($data){
        $npc_data = $this->getNpcData($data);
        $decoded_data = $this->jsonDataToArray($npc_data, TRUE);

        return $decoded_data;
    }

    public function parseData($data){
        try {
            foreach($data as $key => $element){
                $parsed_data['index'] = $key;
                $parsed_data['values']['uiMapId'] = $element[0]['uiMapId'] ?? NULL;
                $parsed_data['values']['uiMapName'] = $element[0]['uiMapName'] ?? NULL;
                $parsed_data['values']['count'] = isset($parsed_data['values']['count']) ? $parsed_data['values']['count'] + $element[0]['count'] : $element[0]['count'];
                $parsed_data['values']['coords'] = isset($parsed_data['values']['coords']) ? array_push($parsed_data['values']['coords'], $element[0]['coords']) : $element[0]['coords'];
            }

            return $parsed_data;
        } catch (GlobalException $th) {
            echo 'ERROR en: '. __FUNCTION__;
            dd($data);
            //dd($element);
        }

    }

    public function appendCoords($original_array, $coords_to_append){
        try {
            if(is_array($coords_to_append)){
                foreach($coords_to_append as $coord){
                    array_push($original_array, $coord);
                }
            }
            return $original_array;
        } catch (GlobalException $ex) {
            echo __FUNCTION__;
            dd($coords_to_append);
        }
    }

    public function getNpcData($data){
        //span class="mapper-map"
        $pattern = "/g_mapperData = (.*)]};/isU";
        $data = TextTransformHelper::decodeUnicodeString($data);
        //file_put_contents('C:\temp\file.txt',$data);
        preg_match_all($pattern, trim(preg_replace('/\s\s+/', ' ', str_replace(array('\n','\t','\r'),'',$data))), $matches);
        if(!isset($matches[1][0])){
            return NULL;
        }

        return $matches[1][0].']}';
    }

    public function jsonDataToArray($elements, $asArray = FALSE){
        if(is_array($elements)){
            $decoded_elements = [];
            foreach($elements as $key => $element){
                $decoded_elements[$key] = json_decode($element, $asArray);
            }
        }
        else{
            $decoded_elements = json_decode($elements, $asArray);
        }

        return $decoded_elements;
    }

    public function getCoordsFromAllNpcForDropping($dropped_by_data){
        foreach($dropped_by_data as $dropping_npc){
            $name = $dropping_npc->name;
            $id = $dropping_npc->id;
            $npc_web_data = $this->getWebData($dropping_npc->id, 'npc', $dropping_npc->name);
            $cleaned_dropped_by_data = $this->getCleanedNpcData($npc_web_data);
            if($cleaned_dropped_by_data == NULL){
                continue;
            }
            $parsed_data = $this->parseData($cleaned_dropped_by_data);
            $parsed_array[$parsed_data['index']]['values']['uiMapId'] = $parsed_data['values']['uiMapId'];
            $parsed_array[$parsed_data['index']]['values']['uiMapName'] = $parsed_data['values']['uiMapName'];
            $parsed_array[$parsed_data['index']]['values']['count'] = isset($parsed_array[$parsed_data['index']]['values']['count'])
                                                                        ? $parsed_array[$parsed_data['index']]['values']['count'] + $parsed_data['values']['count']
                                                                        : $parsed_data['values']['count'];
            $parsed_array[$parsed_data['index']]['values']['coords_normal'] = isset($parsed_array[$parsed_data['index']]['values']['coords'])
                                                                        ? $this->appendCoords($parsed_array[$parsed_data['index']]['values']['coords'], $parsed_data['values']['coords'])
                                                                        : $parsed_data['values']['coords'];
            unset($parsed_data);
        }

        return $parsed_array;
    }

    public function getCoordsFromAllNpcForSkinning($skinned_data){
        foreach($skinned_data as $skinned_npc){
            $name = $skinned_npc->name;
            $id = $skinned_npc->id;
            $classification = $skinned_npc->classification;
            $reaction = $skinned_npc->react[0];
            $npc_web_data = CacheHandler::npcWebCache($skinned_npc->id, $skinned_npc->name);
            $cleaned_skinned_data = $this->getCleanedNpcData($npc_web_data);
            if($cleaned_skinned_data == NULL){
                continue;
            }
            $parsed_data = $this->parseData($cleaned_skinned_data);
            $parsed_array[$parsed_data['index']]['values']['uiMapId'] = $parsed_data['values']['uiMapId'];
            $parsed_array[$parsed_data['index']]['values']['uiMapName'] = $parsed_data['values']['uiMapName'];
            $parsed_array[$parsed_data['index']]['values']['count'] = isset($parsed_array[$parsed_data['index']]['values']['count'])
                                                                        ? $parsed_array[$parsed_data['index']]['values']['count'] + $parsed_data['values']['count']
                                                                        : $parsed_data['values']['count'];
            if($classification == 0){
                if($reaction == -1){
                    $parsed_array[$parsed_data['index']]['values']['coords_normal_aggresive'] = isset($parsed_array[$parsed_data['index']]['values']['coords_normal_aggresive'])
                                                                                ? $this->appendCoords($parsed_array[$parsed_data['index']]['values']['coords_normal_aggresive'], $parsed_data['values']['coords'])
                                                                                : $parsed_data['values']['coords'];
                }
                else{
                    $parsed_array[$parsed_data['index']]['values']['coords_normal'] = isset($parsed_array[$parsed_data['index']]['values']['coords_normal'])
                                                                                ? $this->appendCoords($parsed_array[$parsed_data['index']]['values']['coords_normal'], $parsed_data['values']['coords'])
                                                                                : $parsed_data['values']['coords'];
                }
            }else{
                $parsed_array[$parsed_data['index']]['values']['coords_elite'] = isset($parsed_array[$parsed_data['index']]['values']['coords_elite'])
                                                                        ? $this->appendCoords($parsed_array[$parsed_data['index']]['values']['coords_elite'], $parsed_data['values']['coords'])
                                                                        : $parsed_data['values']['coords'];
            }
            unset($parsed_data);
        }

        return $parsed_array;
    }
}
