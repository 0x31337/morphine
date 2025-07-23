<?php


namespace Morphine\Base\Events;

if (!class_exists(Listener::class))
{
    class Listener
    {

        public static function listen()
        {
            # Check for Events.
            (new Events())();
        }

        // Core Dev note:
        // I can add an extra layer in the listening stage here.
    }
}
