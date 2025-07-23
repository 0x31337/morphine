<?php
/**
 * This file contains global variables that are being used globally in the Application
 * Do not edit any of these unless you know exactly what are you doing !
 * All of these variables are in super-global $GLOBALS pattern as follows: $GLOBALS['var_name']
 * Hint:
 * You should NOT assign anything to this super-global ($GLOBALS) anywhere other than this file
 * To protect you and others from painful debugging.
 */

namespace Morphine\Base\Engine;

class AppGlobals
{
    public static $DB;
    public static $AppDir;
    public static $AppEntryPoint;
    public static $FFMPEGDir;
    public static $ThemesDir;
    public static $AssetsDir;

    public static function init(): void
    {
        self::$DB = new \Morphine\Base\Engine\Database\Database();
        self::$AppDir = getcwd();
        $dirparts = explode(DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR, self::$AppDir, 2);
        if (count($dirparts) > 1) {
            self::$AppEntryPoint = $dirparts[0];
        } else {
            self::$AppEntryPoint = self::$AppDir;
        }
        self::$FFMPEGDir = self::$AppDir . "/base/misc/ffmpeg/";
        self::$ThemesDir = self::$AppDir . "/application/themes/";
        self::$AssetsDir = self::$AppDir . "/assets/";
    }
}
