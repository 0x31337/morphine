<?php

session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('_JASOOS', true);
require 'base/morphine/engine/autoloader.php';


        /*(new \BDR\Renders\Render('mainframe',
            new \BDR\Events\Pages(),
            [])
        );*/
        \Morphine\Events\Listener::listen();
