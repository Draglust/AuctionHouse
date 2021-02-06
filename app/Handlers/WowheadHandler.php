<?php

namespace App\Handlers;

use App\Models\Item;
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
            $url = $this->selectUrlType($type).$id.'/'.strtolower($this->customUrlEncode(str_replace(' ','-',$name)));
            $data = Http::get($url);
            file_put_contents('C:\temp\file.txt',json_encode($data->headers()).'\r\n', FILE_APPEND);
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

    public function customUrlEncode($text){
        $text = str_replace('ú','%C3%BA',$text);
        $text = str_replace('á','%C3%A1',$text);
        $text = str_replace('é','%C3%A9',$text);
        $text = str_replace('í','%C3%AD',$text);
        $text = str_replace('ó','%C3%B3',$text);
        $text = str_replace('ñ','%C3%B1',$text);

        return $text;
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
        $dropped_by_data = $this->getDroppedByDataFromWebData($data);
        $cleaned_data = $this->cleanData($dropped_by_data);
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
        foreach($coords_to_append as $coord){
            array_push($original_array, $coord);
        }
        return $original_array;
    }

    public function getNpcData($data){
        //span class="mapper-map"
        $pattern = "/g_mapperData = (.*)]};/isU";
        $data = $this->decodeUnicodeString($data);
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

    public function cleanData($data){
        $each_found = explode('},', $data);
        foreach($each_found as $index => $each_dropped){
            $each_found[$index] = $each_dropped.'}';
        }
        return $each_found;
    }

    public function getDroppedByDataFromWebData($data){
        $pattern = "/dropped-by(.*)data: \[(.*)}],(.*)\);/isU";
        $data = $this->decodeUnicodeString($data);
        //file_put_contents('C:\temp\file.txt',$data);
        preg_match_all($pattern, trim(preg_replace('/\s\s+/', ' ', str_replace(array('\n','\t','\r'),'',$data))), $matches);
        if(!isset($matches[2][0])){
            return NULL;
        }

        return $matches[2][0];
    }

    public function decodeUnicodeString($string){
        $string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $string);

        return $string;
    }
}
