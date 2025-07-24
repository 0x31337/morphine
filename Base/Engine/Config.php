<?php
namespace Morphine\Base\Engine;

class Config
{
    private static array $cache = [];

    public static function get(string $name)
    {
        if ($name === 'database') {
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (!file_exists($envPath)) {
                // User-friendly message if .env is missing
                if (php_sapi_name() === 'cli') {
                    fwrite(STDERR, "\n[ERROR] .env file not found at project root.\nPlease run the install command (php morph install) or create one manually.\n\n");
                    exit(1);
                } else {
                    header('HTTP/1.1 503 Service Unavailable');
                    echo "<html><head><title>Setup Required</title></head><body style='font-family:sans-serif;background:#f8f8f8;color:#222;text-align:center;padding:3em;'><h1>Setup Required</h1><p>The <code>.env</code> file is missing at the project root.</p><p>Please run the <b>install</b> command or create a <code>.env</code> file manually before proceeding.</p></body></html>";
                    exit;
                }
            }
            $env = parse_ini_file($envPath);
            return [
                'host' => $env['DB_HOST'] ?? '127.0.0.1',
                'name' => $env['DB_NAME'] ?? 'morphine_app',
                'user' => $env['DB_USER'] ?? 'root',
                'password' => $env['DB_PASS'] ?? '',
            ];
        }
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        $file = dirname(__DIR__, 2) . '/Application/config/' . $name . '.php';
        if (file_exists($file)) {
            return self::$cache[$name] = require $file;
        }
        throw new \Exception("Config file '$name' not found in /Application/config/");
    }
} 