<?php

namespace App\Helpers;

use App\Models\Token;
use Exception as GlobalException;

class TextTransformHelper
{
    public static function getDroppedByDataFromWebData($data){
        $pattern = "/dropped-by(.*)data: \[(.*)}],(.*)\);/isU";
        $data = TextTransformHelper::decodeUnicodeString($data);
        //file_put_contents('C:\temp\file.txt',$data);
        preg_match_all($pattern, trim(preg_replace('/\s\s+/', ' ', str_replace(array('\n','\t','\r'),'',$data))), $matches);
        if(!isset($matches[2][0])){
            return NULL;
        }

        return $matches[2][0];
    }

    public static function getSkinnedDataFromWebData($data){
        $pattern = "/skinned-from(.*)data: \[(.*)}],(.*)\);/isU";
        $data = TextTransformHelper::decodeUnicodeString($data);
        //file_put_contents('C:\temp\file.txt',$data);
        preg_match_all($pattern, trim(preg_replace('/\s\s+/', ' ', str_replace(array('\n','\t','\r'),'',$data))), $matches);
        if(!isset($matches[2][0])){
            return NULL;
        }

        return $matches[2][0];
    }

    public static function decodeUnicodeString($string){
        $string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $string);

        return $string;
    }

    public static function cleanData($data){
        $each_found = explode('},', $data);
        foreach($each_found as $index => $each_dropped){
            $each_found[$index] = $each_dropped.'}';
        }
        return $each_found;
    }

    public static function customUrlEncode($text){
        $text = str_replace('ú','%C3%BA',$text);
        $text = str_replace('á','%C3%A1',$text);
        $text = str_replace('é','%C3%A9',$text);
        $text = str_replace('í','%C3%AD',$text);
        $text = str_replace('ó','%C3%B3',$text);
        $text = str_replace('ñ','%C3%B1',$text);

        return $text;
    }
}
