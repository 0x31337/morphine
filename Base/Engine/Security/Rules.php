<?php


namespace Morphine\Base\Engine\Security;

use Morphine\Events\Display;
use Morphine\Events\Events;
use Morphine\Base\Engine\AppGlobals;

class Rules
    {
        public static function latinAlpha($input)
        {
            return preg_match('/^[\p{Latin}\s]+$/m', $input);
        }
        public static function arabicAlpha($input)
        {
            return preg_match("/^[\p{Arabic}\s]+$/u", $input);
        }


        public static function isCountry($input):bool
        {
            $countries_json_path = AppGlobals::$AssetsDir . 'countries.json';
            $countries = json_decode(file_get_contents($countries_json_path));
            return false !== array_search($input,
                array_map(function($country){
                    return $country->code;
                }, $countries));
        }

        public static function yesOrNo($param)
        {
            return trim(strtolower($param)) == 'yes' || trim(strtolower($param)) == 'no';
        }

        public static function trueOrFalse($param)
        {
            return trim(strtolower($param)) == 'true' || trim(strtolower($param)) == 'false';
        }

    }