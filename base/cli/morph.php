<?php
/**
 * Morph Command-Line Interface . PHP script
 */
require "morph.lib.php";
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
print_success("Welcome to Morph Command-Line Interface !!");
foreach ($argv as $k=>$arg)
{
    if($arg == "install") install();
    if($arg == "list") clist();
    if($arg == "pack") print "pack command\n";
    if($arg == "whatis") whatis($argv[$k+1]??false);
}
start:
prompt();
$cmd = gets();
switch (trim($cmd))
{
    case 'install':
        if(!install()) goto start;
        goto start;
    case 'pack':
        pack();
        goto start;
    case 'list':
        clist();
        goto start;
    case 'exit':
        exit();
    default:
        print_fail("Command ".trim($cmd)." does not exist.");
        goto start;
}
?>