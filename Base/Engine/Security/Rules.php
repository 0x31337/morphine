<?php


namespace Morphine\Base\Engine\Security;

use Morphine\Events\Display;
use Morphine\Events\Events;
use Morphine\Base\Engine\AppGlobals;
use Morphine\Base\Engine\Config;

class Rules
    {
        public static function __callStatic($name, $arguments)
        {
            $customRules = Config::get('rules');
            if (isset($customRules[$name]) && is_callable($customRules[$name])) {
                return call_user_func_array($customRules[$name], $arguments);
            }
            throw new \BadMethodCallException("Rule '$name' not found in custom rules.");
        }

    }