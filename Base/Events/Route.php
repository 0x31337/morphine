<?php


namespace Morphine\Base\Events;


use Morphine\Base\Events\Dispatcher\Trail;
use Morphine\Base\Engine\Config;

class Route extends Events
{
    public static function trace(string $route_path, Trail $trail)
    {
        $routes = Config::get('routes');
        $route_parts = explode('/', $route_path);
        $landing_parameter = @$route_parts[1];
        if (isset($routes[$landing_parameter])) {
            $trail->target = $routes[$landing_parameter];
        } else {
            $trail->target = '404';
        }
        return $trail;
    }

    public static function redirect($target_uri)
    {
        header("Location: $target_uri");
        // https://thedailywtf.com/articles/WellIntentioned-Destruction
        die(); // -----------------------------^
    }

    /**
     * Don't over-use this method, it's been created to manage special situations
     * where you really need a redirect in the middle of and html page
     * (a.k.a inside a nested View, post-verification process)
     * TIP: You're encouraged to process verifications and redirects prior to the render process.
     * > Please read the Documentation about ("MORE") structure and Morphine framework as well.
     * @param $target_uri
     */
    public static function html_redirect($target_uri)
    {
        echo "<meta http-equiv='refresh' content='0; URL=$target_uri' />";
        // https://thedailywtf.com/articles/WellIntentioned-Destruction
        die(); // -----------------------------^
    }

    private static function strict_uri_policy($denied_parameter_index, &$trail): void
    {
        if (!empty($denied_parameter_index)) {
            $trail->target = '404';
        }
    }
}