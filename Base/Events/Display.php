<?php

/**
 * Display class, responsible for rendering the right page to the user
 */

namespace Morphine\Base\Events;

use Morphine\Base\Renders\Render;
use Morphine\Base\Events\Pages;

if(!class_exists(Display::class))
{
    class Display
    {
        static public bool $asset_call_once;
        public static function _render($requested_view, $req_data)
        {
            self::$asset_call_once = false;
            (function()use($requested_view, $req_data) {
                return new Render($requested_view,
                    new Pages(),
                    $req_data);
            })(
                $requested_view,
                new Pages(),
                $req_data
            );
        }
    }

    // Core Dev note:
    // I can inject a plugin hook here for example for tracking and logging user browsing activity
    // Plugins are in my to-do list, not yet implemented.
    // I am building a Plugins Engine to Morph CLI yet to be announced in later updates.
}