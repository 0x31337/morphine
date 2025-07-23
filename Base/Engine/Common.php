<?php
/**
 * This is a set of functions that are commonly used across the Application
 * these functions are now declared in the global scope and are able to be used without class or namespace suffixes.
 * A list of these common functions with further explanation is available in the documentation
 * -----------------------------------------------------------------------------------------------------------------
 */

namespace Morphine\Base\Engine;

class Common
{
    public static function reqType(string $type = 'REQUEST_URI', $arg = false)
    {
        switch ($type) {
            case 'GET':
                return ($arg) ? $_GET[$arg] : $_GET;
            case 'POST':
                return ($arg) ? $_POST[$arg] : $_POST;
            case 'SERVER':
                return ($arg) ? $_SERVER[$arg] : $_SERVER;
            case 'COOKIE':
                return ($arg) ? $_COOKIE[$arg] : $_COOKIE;
            case 'REQUEST_URI':
                return $_SERVER['REQUEST_URI'];
            case 'FILES':
                return ($arg) ? $_FILES[$arg] : $_FILES;
            case 'SESSION':
                return ($arg) ? $_SESSION[$arg] : $_SESSION;
            default:
                return false;
        }
    }

    public static function req($param)
    {
        if (@$_GET[$param]) {
            return $_GET[$param];
        } elseif (@$_POST[$param]) {
            return $_POST[$param];
        }
    }

    public static function userId()
    {
        return $_SESSION['id'] ?? false;
    }

    public static function isSuperAdmin()
    {
        return ($_SESSION['id'] ?? null) == 1;
    }

    public static function escape($string)
    {
        return isset($string) ? htmlentities($string) : null;
    }

    public static function currentDate($pattern = 'Y/m/d')
    {
        return date($pattern, time());
    }

    public static function timeAgo($time)
    {
        $time_difference = time() - $time;
        if ($time_difference < 1) {
            return 'less than 1 second ago';
        }
        $condition = [
            12 * 30 * 24 * 60 * 60 => 'سنة',
            30 * 24 * 60 * 60 => 'شهر',
            24 * 60 * 60 => 'يوم',
            60 * 60 => 'ساعة',
            60 => 'دقيقة',
            1 => 'ثانية'
        ];
        foreach ($condition as $secs => $str) {
            $d = $time_difference / $secs;
            if ($d >= 1) {
                $t = round($d);
                return ' منذ ' . $t . ' ' . $str . ($t > 1 ? ' ' : '');
            }
        }
    }

    public static function viewsLoader($className)
    {
        $classFullPath = getcwd() . '/application/views/' . $className . '.php';
        if (file_exists($classFullPath)) {
            require_once $classFullPath;
            if (class_exists('\\Morphine\\Application\\Views\\' . $className)) {
                return true;
            }
        }
        return false;
    }

    public static function loadImg($imgName, $themeAbsoluteDir)
    {
        $themeDir = str_replace(AppGlobals::$AppEntryPoint, '', $themeAbsoluteDir);
        return $themeDir . '/assets/' . \Morphine\Base\Engine\Assets::img[$imgName];
    }

    public static function homeUrl()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
            "://" . $_SERVER['HTTP_HOST'] . '/';
    }

    public static function translate($text)
    {
        // Placeholder for translation logic
        return $text;
    }

    public static function echoSecure($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}