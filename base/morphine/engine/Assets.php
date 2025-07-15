<?php
/**
 * Class Assets: responsible for loading these types of assets :
 * > CSS, JavaScript, Img (& SVG)
 * // TODO : include assets relative to the theme path
 */
namespace Morphine\Engine;

if (!class_exists(Assets::class))
{
    class Assets
    {
        private string $theme_name;

        private string $main_frame_assets_dir;


        public array $js = array(
            'jquery' => 'js/jquery-3.6.0.min.js',
            'globals' => 'js/globals.js',
            'websocket' => 'js/websocket.js',
            'ws.handle' => 'js/ws.handle.js'

            //'main_frame' => 'js/main_frame.js'

        );

        public array $css = array(
            # globals.css contains styles be present in every page
            'bootstrap' => 'css/bootstrap.min.css',
            'globals' => 'css/globals.css'
        );

        public const img = array(
          'zerg_logo' => 'img/logo.png',
          'zerg_header' => 'img/header.png'
        );

        public function __construct($theme_name)
        {
            $this->theme_name = $theme_name;
        }

        # Load specific scripts only if a specific view is rendered
        public function conditional_load($view_name)
        {
            switch ($view_name)
            {
                case 'mainframe':
                    self::load_css('mainframe');
                    self::load_js('mainframe');
                    break;
            }
        }

        # Main method to Load the scripts
        public function load(string $view_name)
        {
            # Loading global scripts that should be present everywhere in the app
            # Please note :
            # custom themes (through conditional_load method above) can override any CSS element loaded in global scope
            # as well as JS functions from the global scope may conflict with ones in the conditional_load scripts
            if($view_name != "ajax")
            {
                if(false === \Morphine\Events\Display::$asset_call_once)
                {
                    $this->load_global_css('bootstrap');
                    $this->load_global_css('globals');
                    $this->load_global_js('jquery');
                    $this->load_global_js('globals');
                    $this->load_global_js('ajax');
                }
            }

            $this->conditional_load($view_name);
            return $this;
        }

        public function load_global_js($key)
        {
            //printf( '<script src="%s"></script>',$this->js[$key]);
            $this->check_and_load($key, 'js');
        }

        public function load_global_css($key)
        {
            //printf( "<link rel='stylesheet' href='%s' type='text/css'>", $this->css[$key]);
            $this->check_and_load($key, 'css');
        }

        public function load_js($key)
        {
            $this->check_and_load($key, 'js');
        }

        public function load_css($key)
        {
            $this->check_and_load($key, 'css');
        }

        public function load_img($key)
        {
            $this->check_and_load($key, $this->img);
        }

        // ----------------------------------------------------------

        public function check_and_load($key, $type)
        {
            if (array_key_exists($key, $this->$type))
            {
                $assets = '/application/themes/'.$this->theme_name.'/assets';
                $file = $assets . '/' . $this->$type[$key];
                # Fallback to main assets directory if not theme-specific
                if(!file_exists($file)) {
                    $assets = '/application/assets';
                    $file = $assets . '/' . $this->$type[$key];
                }
                if (!empty($key))
                {
                    switch ($type)
                    {
                        case 'js':
                            printf( '<script src="%s"></script>',$file);
                            break;
                        case 'css':
                            printf( "<link rel='stylesheet' href='%s' type='text/css'>", $file);
                            break;
                    }
                }
            }
        }

        /**
         * This function loads images according to theme
         * @todo: refine this so the designer can include his own images in the theme's files
         * @param $theme_name
         */
        public function register_images($theme_name)
        {
            $this->img[1] = array(
                'oops' => '/application/themes/' . $theme_name . '/assets/img/oops.png'
            );
        }
    }
}