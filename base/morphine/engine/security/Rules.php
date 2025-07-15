<?php


namespace Morphine\Engine\Security;

use Morphine\Events\Display;
use Morphine\Events\Events;

class Rules
    {
        public static function latin_alpha($input)
        {
            return preg_match('/^[\p{Latin}\s]+$/m', $input);
        }
        public static function arabic_alpha($input)
        {
            return preg_match("/^[\p{Arabic}\s]+$/u", $input);
        }



        public static function is_country($input):bool
        {
            $countries_json_path = $GLOBALS['Assets.Dir'].'countries.json';
            $countries = json_decode(file_get_contents($countries_json_path));
            return false !== array_search($input,
                array_map(function($country){
                    return $country->code;
                }, $countries));
        }

        public static function yes_or_no($param)
        {
            return trim(strtolower($param)) == 'yes' || trim(strtolower($param)) == 'no';
        }

        public static function true_or_false($param)
        {
            return trim(strtolower($param)) == 'true' || trim(strtolower($param)) == 'false';
        }
    }
