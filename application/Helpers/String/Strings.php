<?php

namespace Agencia\Close\Helpers\String;

class Strings
{
    public static function removePreposition(string $preposition)
    {
        $wordsToRemove = array("da", "de", "di", "do", "du", "para", "pra", "em", "in", "por", "até", "ate");
        return preg_replace('/\b(' . implode('|', $wordsToRemove) . ')\b/', '', $preposition);
    }

    public static function getToString(array $gets): string
    {
        unset($gets['route'], $gets['data']);
        $stringGet = '?';
        $index = 0;
        foreach ($gets as $key => $get) {
            if ($index !== 0) {
                $stringGet .= '&';
            }
            $stringGet .= $key . '=' . $get;
            $index++;
        }
        return $stringGet;
    }

    public static function getToSearch(array $gets): string
    {
        $page = explode('/', $gets['route']);
        if(!empty($page[2])){
            if( ($page[2] == 'lancamentos') || ($page[2] == 'lista') || ($page[2] == 'placa') || ($page[2] == 'minha-lista') ){
                $pageSearch = $page[2];
            }else{
                $pageSearch = 'produtos';
            }
        }else{
            $pageSearch = 'produtos';
        }
        return $pageSearch;
    }

    public static function convertCommaForFormatToInSQl(string $string): string
    {
         $arrayString = explode(',', $string);
         return "('" . implode("','", $arrayString) . "')";
    }

}