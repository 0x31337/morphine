<?php
/**
 * This file contains global variables that are being used globally in the Application
 * Do not edit any of these unless you know exactly what are you doing !
 * All of these variables are in super-global $GLOBALS pattern as follows: $GLOBALS['var_name']
 * Hint:
 * You should NOT assign anything to this super-global ($GLOBALS) anywhere other than this file
 * To protect you and others from painful debugging.
 * -------------------------------------------------------------------------------------------------
 * If you'd like to contribute with a plugin and you need to assign a new global, please follow the framework
 * standards by reading the Plugins API docs to find out how to do that without direct interaction with this file.
 * If somehow you explicitly hardcode this file, the next versions of BDR will most likely override your input .
 * > Do not declare constants in this file if you're going to edit the BDR Core ; declare only in this
 * > super-global pattern, in order to give freedom to Plugin devs to alter anything depending on use cases.
 */


/**
 * 1 - General Config :
 *      * NodeJS server IP:PORT;
 *      * MySQL Server IP;
 *      * MySQL database instance 'Database'
 */

# The IP 10.1.74.49ess of the nodeJS server that is used for facial recognition:
$GLOBALS['nodeJS.server'] = "10.1.74.46:8080";

# The IP of the current BDR Application.
$GLOBALS['Client.Addr.Http'] = "http://".gethostbyname(gethostname());
$GLOBALS['Client.Addr.Https'] = "https://".gethostbyname(gethostname());


# Initializing Database object:
# Hint: You can change DB name, username & password in the private preferences of Database Class.
$GLOBALS['DB'] = new \Morphine\Engine\Database();


$GLOBALS['App.EntryPoint'] = 'E:\\Classified\\Z\\C&C'; // No Trailing slash !

/**
 * 2 - Important Directories
     * NOTE: trailing slash is important.
 */
$GLOBALS['App.Dir'] = getcwd();


$GLOBALS['FFMPEG.Dir'] = $GLOBALS['App.Dir']."/assets/ffmpeg/";

$GLOBALS['Themes.Dir'] = $GLOBALS['App.Dir']."/application/themes/";

$GLOBALS['Assets.Dir'] = $GLOBALS['App.Dir']."/assets/";
