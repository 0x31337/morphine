<?php

session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('_JASOOS', true);
require 'vendor/autoload.php';

\Morphine\Base\Engine\AppGlobals::init();
\Morphine\Base\Events\Listener::listen();
