<?php
/**
 * This is a set of functions that are commonly used across the Application
 * these functions are now declared in the global scope and are able to be used without class or namespace suffixes.
 * A list of these common functions with further explanation is available in the documentation
 * -----------------------------------------------------------------------------------------------------------------
 */

/**
 * Implementing the ability to add an operation when a special event 'hook' happens
 * @param $hook
 * @param callable $callable
 */
function add_operation($hook, callable $callable)
{

}

/**
 * Implementing the ability to register an event 'hook', to attach other operations to it later
 * @param $hook
 */
function register_hook($hook)
{

}

/**
 * Receiving different requests, sanitizing and securing it in the process
 * @param string $type : default = REQUEST_URI
 * @param $arg
 * @return false|string
 */
function _req($type='REQUEST_URI', $arg=false)
{
    switch ($type)
    {
        case 'GET':
            return ($arg)?$_GET[$arg]:$_GET;

        case 'POST':
            return ($arg)?$_POST[$arg]:$_POST;

        case 'SERVER':
            return ($arg)?$_SERVER[$arg]:$_SERVER;

        case 'COOKIE':
            return ($arg)?$_COOKIE[$arg]:$_COOKIE;

        case 'REQUEST_URI':
            return $_SERVER['REQUEST_URI'];

        case 'FILES':
            return ($arg)?$_FILES[$arg]:$_FILES;

        default:
            return false;
    }
}

/**
 * Retrieves POST or GET requests only, one at a time, priority to GET.
 * @param $param
 * @return mixed
 */
function req($param)
{
    if (@$_GET[$param]) {
        return $_GET[$param];
    }
    elseif (@$_POST[$param]) {
        return $_POST[$param];
    }
}


/**
 * Checks if the current visitor is actually logged in to his account
 */
function is_logged_in()
{

}

/**
 * Returns the user_id of the current user
 */
function user_id()
{
    return $_SESSION['id']??false;
}

function is_super_admin()
{
    return $_SESSION['id']==1??false;
}

function username()
{
    $db = $GLOBALS['DB'];
    $where = array(
        'id' => user_id()
    );
    $db->select('*', 'users', $where);

    $total = $db->getTotalRows();

    if ($total > 0) while ($row = $db->exists()) return $row['username'];
    return 'anonymous'; // means something wrong.
}

/**
 * Returns the permissions of the current user
 * @param $user_id
 */
function user_perms()
{
    return $_SESSION['perms']??false;
}


/**
 * An i18 standard library call
 */
function __($string)
{
    if(isset($string))
    {
        return htmlentities($string);
    }
}

/**
 * Returns the current date only by default,
 * can be modified to date-and-time by changing the $pattern argument
 * @param string $pattern
 * @return false|string
 */
function current_date($pattern='Y/m/d')
{
    return date($pattern, time());
}

function timeAgo($time)
{
    $time_difference = time() - $time;

    if ($time_difference < 1) {
        return 'less than 1 second ago';
    }
    $condition = array(12 * 30 * 24 * 60 * 60 => 'سنة',
        30 * 24 * 60 * 60 => 'شهر',
        24 * 60 * 60 => 'يوم',
        60 * 60 => 'ساعة',
        60 => 'دقيقة',
        1 => 'ثانية'
    );

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;

        if ($d >= 1) {
            $t = round($d);
            return ' منذ ' . $t . ' ' . $str . ($t > 1 ? ' ' : '');
        }
    }
}

function get_var_name( &$var, $scope=false, $prefix='UNIQUE', $suffix='VARIABLE' )
{
    if($scope) {
        $vals = $scope;
    } else {
        $vals = $GLOBALS;
    }
    $old = $var;
    $var = $new = $prefix.rand().$suffix;
    $vname = FALSE;
    foreach($vals as $key => $val) {
        if($val === $new) $vname = $key;
    }
    $var = $old;
    return $vname;
}

function views_loader($class_name)
{
    $class_full_path = getcwd().'/application/views/'.$class_name.'.php';
    if(file_exists($class_full_path))
    {
        require_once $class_full_path;
        if(class_exists('\\Application\\Views\\'.$class_name))
        {
            return true;
        }
    }
    return false;
}

function load_img($img_name, $theme_absolute_dir)
{
    $theme_dir = str_replace($GLOBALS['App.EntryPoint'], '', $theme_absolute_dir);
    return $theme_dir . '/assets/'.\Morphine\Engine\Assets::img[$img_name];
}

function home_url()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']
        === 'on' ? "https" : "http") .
        "://" . $_SERVER['HTTP_HOST'] . '/';
}

function helper_sortByFirstInstallTimeDesc($a, $b) {
    return $b['firstInstallTime'] - $a['firstInstallTime'];
}