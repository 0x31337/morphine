<?php
/**
 * Morph Command-Line Interface . PHP script
 */
namespace Morphine\Base\CLI;

use Morphine\Base\CLI\CliUtils;

require_once __DIR__ . '/../../vendor/autoload.php';

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

CliUtils::printSuccess("Welcome to Morph Command-Line Interface !!");

function dispatchCommand($cmd, $argv) {
    $cmd = trim($cmd);
    $parts = preg_split('/\s+/', $cmd);
    $main = strtolower($parts[0] ?? '');
    $arg1 = $parts[1] ?? null;

    switch ($main) {
        case 'install':
            CliUtils::install();
            break;
        case 'pack':
            CliUtils::package();
            break;
        case 'create':
            CliUtils::create($arg1);
            break;
        case 'channels':
            CliUtils::showChannels();
            break;
        case 'channel':
            if ($arg1) CliUtils::showChannel($arg1);
            else CliUtils::printFail("Usage: channel <name>");
            break;
        case 'surface':
            if ($arg1) CliUtils::showSurfaceDetails($arg1);
            else CliUtils::printFail("Usage: surface <channel:surface>");
            break;
        case 'traceview':
            CliUtils::traceView($arg1);
            break;
        case 'traceop':
            if ($arg1) CliUtils::traceop($arg1);
            else CliUtils::printFail("Usage: traceop <operation>");
            break;
        case 'broken_views':
            CliUtils::checkBrokenViews();
            break;
        case 'dump':
            CliUtils::dump();
            break;
        case 'list':
            CliUtils::clist();
            break;
        case 'exit':
            exit();
        default:
            CliUtils::printFail("Command '" . $cmd . "' does not exist.");
            break;
    }
}

while (true) {
    CliUtils::prompt();
    $cmd = CliUtils::gets();
    dispatchCommand($cmd, $argv);
}
?>