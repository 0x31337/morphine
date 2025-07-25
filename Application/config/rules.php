<?php
// Application custom validation rules
return [
    'latinAlpha' => function($input) {
        return preg_match('/^[\p{Latin}\s]+$/m', $input);
    },
    'arabicAlpha' => function($input) {
        return preg_match("/^[\p{Arabic}\s]+$/u", $input);
    },
    'isCountry' => function($input) {
        $countries_json_path = \Morphine\Base\Engine\AppGlobals::$AssetsDir . 'countries.json';
        $countries = json_decode(file_get_contents($countries_json_path));
        return false !== array_search($input,
            array_map(function($country){
                return $country->code;
            }, $countries));
    },
    'yesOrNo' => function($param) {
        return trim(strtolower($param)) == 'yes' || trim(strtolower($param)) == 'no';
    },
    'trueOrFalse' => function($param) {
        return trim(strtolower($param)) == 'true' || trim(strtolower($param)) == 'false';
    },
    // Email validation rule
    'email' => function($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    },
    // Add more custom rules as needed
]; 