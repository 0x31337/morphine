<?php
/**
 * The Class where to declare new surfaces
 */
namespace Morphine\Base\Events\Dispatcher;

use Morphine\Base\Engine\Config;

class Channels
{
    public static array $channels;

    /**
     * This is where you need to add your new Surface
     */
    public static function init()
    {
        self::$channels = Config::get('channels');
    }

    public static function exists($channel_name)
    {
        return in_array($channel_name, array_keys(self::$channels));
    }

    public static function get($channel_name)
    {
        return self::$channels[$channel_name];
    }
}
