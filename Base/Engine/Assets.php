<?php
/**
 * Class Assets: responsible for loading CSS, JavaScript, and image assets.
 * All configuration is in Application/config/assets.php.
 */
namespace Morphine\Base\Engine;

use Morphine\Base\Events\Display;

class Assets
{
    private string $theme_name;
    private array $config = [];

    public function __construct($theme_name)
    {
        $this->theme_name = $theme_name;
        // Robustly load asset config from userspace (case-sensitive, always correct)
        $configPath = dirname(__DIR__, 2) . '/Application/config/assets.php';
        $config = @include($configPath);
        $this->config = is_array($config) ? $config : [];
    }

    // Main method to load all assets for a view
    public function load(string $view_name)
    {
        if ($view_name !== "ajax") {
            if (false === Display::$asset_call_once) {
                // Load global assets
                foreach (($this->config['global']['css'] ?? []) as $cssKey) {
                    $this->loadCss($cssKey);
                }
                foreach (($this->config['global']['js'] ?? []) as $jsKey) {
                    $this->loadJs($jsKey);
                }
            }
        }
        // Load per-view (conditional) assets
        if (!empty($this->config['views'][$view_name])) {
            foreach (['css', 'js'] as $type) {
                foreach (($this->config['views'][$view_name][$type] ?? []) as $key) {
                    if ($type === 'css') {
                        $this->loadCss($key);
                    } elseif ($type === 'js') {
                        $this->loadJs($key);
                    }
                }
            }
        }
        return $this;
    }

    public function loadJs($key)  { $this->outputAsset($key, 'js'); }
    public function loadCss($key) { $this->outputAsset($key, 'css'); }
    public function loadImg($key) { $this->outputAsset($key, 'img'); }

    // Output the asset tag for the given key and type
    private function outputAsset($key, $type)
    {
        $file = $this->resolveAssetPath($key, $type);
        if (!$file) return;
        switch ($type) {
            case 'js':
                printf('<script src="%s"></script>', $file);
                break;
            case 'css':
                printf("<link rel='stylesheet' href='%s' type='text/css'>", $file);
                break;
            case 'img':
                printf('<img src="%s" alt="%s">', $file, $key);
                break;
        }
    }

    // Resolve the asset file path, checking theme assets first, then falling back to /application/assets
    private function resolveAssetPath($key, $type)
    {
        $assets = '/Application/Themes/' . $this->theme_name . '/assets';
        $file = $this->config[$type][$key] ?? null;
        $projectRoot = getcwd();
        $themePath = $projectRoot . $assets . '/' . $file;
        $appAssets = '/Application/Assets';
        $appPath = $projectRoot . $appAssets . '/' . $file;

        // Reminder: Directory and file names are case sensitive on most systems!

        if (!$file) return null;
        if (file_exists($themePath)) {
            return $assets . '/' . $file;
        }
        if (file_exists($appPath)) {
            return $appAssets . '/' . $file;
        }
        return null;
    }
}