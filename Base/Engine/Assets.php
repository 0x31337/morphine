<?php
/**
 * Class Assets: responsible for loading these types of assets :
 * > CSS, JavaScript, Img (& SVG)
 */
namespace Morphine\Base\Engine;

use Morphine\Base\Events\Display;

class Assets
{
    const img = [];
    private string $theme_name;
    private string $main_frame_assets_dir;

    public array $js = [
        'jquery' => 'js/jquery-3.6.0.min.js',
        'globals' => 'js/globals.js',
        // 'main_frame' => 'js/main_frame.js',
    ];

    public array $css = [
        // globals.css contains styles be present in every page
        'bootstrap' => 'css/bootstrap.min.css',
        'globals' => 'css/globals.css',
        'guestframe' => 'css/guestframe.css',
    ];

    public static array $img = [
        'logo' => 'img/logo.png',
        'header' => 'img/header.png',
    ];

    public function __construct($theme_name)
    {
        $this->theme_name = $theme_name;
    }

    // Load specific scripts only if a specific view is rendered
    public function conditionalLoad($view_name)
    {
        switch ($view_name) {
            case 'guestframe':
                $this->loadCss('guestframe');
                break;
        }
    }

    // Main method to Load the scripts
    public function load(string $view_name)
    {
        // Loading global scripts that should be present everywhere in the app
        // Please note :
        // custom themes (through conditional_load method above) can override any CSS element loaded in global scope
        // as well as JS functions from the global scope may conflict with ones in the conditional_load scripts
        if ($view_name != "ajax") {
            if (false === Display::$asset_call_once) {
                $this->loadGlobalCss('bootstrap');
                $this->loadGlobalCss('globals');
                $this->loadGlobalJs('jquery');
                $this->loadGlobalJs('globals');
                $this->loadGlobalJs('ajax');
            }
        }

        $this->conditionalLoad($view_name);
        return $this;
    }

    public function loadGlobalJs($key)
    {
        $this->checkAndLoad($key, 'js');
    }

    public function loadGlobalCss($key)
    {
        $this->checkAndLoad($key, 'css');
    }

    public function loadJs($key)
    {
        $this->checkAndLoad($key, 'js');
    }

    public function loadCss($key)
    {
        $this->checkAndLoad($key, 'css');
    }

    public function loadImg($key)
    {
        $this->checkAndLoad($key, self::$img);
    }

    // ----------------------------------------------------------------------------

    public function checkAndLoad($key, $type)
    {
        if (array_key_exists($key, $this->$type)) {
            $assets = '/application/themes/' . $this->theme_name . '/assets';
            $file = $assets . '/' . $this->$type[$key];
            // Fallback to main assets directory if not theme-specific
            if (!file_exists($file)) {
                $assets = '/application/assets';
                $file = $assets . '/' . $this->$type[$key];
            }
            if (!empty($key)) {
                switch ($type) {
                    case 'js':
                        printf('<script src="%s"></script>', $file);
                        break;
                    case 'css':
                        printf("<link rel='stylesheet' href='%s' type='text/css'>", $file);
                        break;
                }
            }
        }
    }
}