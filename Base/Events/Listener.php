<?php


namespace Morphine\Base\Events;

class Listener
{

    public static function listen()
    {
        // Check for Events.
        (new Events())();
    }

    // Core Dev note:
    // I can add an extra layer in the listening stage here.
}
