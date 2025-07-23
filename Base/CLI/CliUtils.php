<?php
/**
 * Functions Library file for Morph Command-Line Interface.
 */
namespace Morphine\Base\CLI;

use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Morphine\Base\Events\Dispatcher\Channels;

class CliUtils
{
    private static function getProjectRoot(): string
    {
        $dir = __DIR__;
        while (!file_exists($dir . DIRECTORY_SEPARATOR . 'composer.json')) {
            $parent = dirname($dir);
            if ($parent === $dir) break;
            $dir = $parent;
        }
        return $dir;
    }

    public static function printSuccess($message)
    {
        print "[+] $message\n";
    }
    public static function printFail($message)
    {
        print "[-] $message\n";
    }
    public static function printWarning($message)
    {
        print "[!] $message\n";
    }
    public static function printInfo($message)
    {
        print "[i] $message\n";
    }
    public static function printQuestion($message)
    {
        print "[->] $message ";
    }

    public static function prompt()
    {
        print "morph-shell> ";
    }

    public static function gets(int $length=255)
    {
        return fread(STDIN, 255);
    }

    public static function install()
    {
        self::printInfo('Morphine framework installation process...');
        self::printQuestion('Enter your MySQL database server address :');
        $server_addr = self::gets();
        self::printQuestion('Enter your MySQL username : ');
        $user_name = self::gets();
        insert_password:
        self::printQuestion('Enter your MySQL Password : ');
        $pass_word = self::gets();
        if($pass_word == $user_name)
        {
            self::printWarning("You can't use the same string as username and password ! ");
            goto insert_password;
        }
        insert_dbname:
        self::printQuestion('Enter the database name you wish to use : ');
        $db_name = self::gets();
        if($db_name == $user_name)
        {
            self::printWarning("You can't use the same string as username and database name for security reasons !");
            goto insert_dbname;
        }
        if($db_name == $pass_word)
        {
            self::printWarning("You can't use the same string as password and database name for security reasons !");
            goto insert_dbname;
        }

        $dbPath = self::getProjectRoot() . '/Base/Engine/Database/Database.php';
        if(file_exists($dbPath)) {
            $databasefile = file_get_contents($dbPath);
        } else {
            print($dbPath . "\n");
            self::printWarning("can't find database files, Morphine filesystem is corrupt, please check with original github clone");
            self::printWarning("|__ ( please note that morph shell should be inside /base/cli/ directory ) ");
            self::printFail('Unable to install Morphine framework.');
            return false;
        }

        // Trim all user input to avoid whitespace and \r\n issues
        $server_addr = trim($server_addr);
        $db_name = trim($db_name);
        $user_name = trim($user_name);
        $pass_word = trim($pass_word);

        $output_dbfile = self::update_dbfile($databasefile, $server_addr, $db_name, $user_name, $pass_word);
        fwrite(fopen($dbPath, 'w'), $output_dbfile);
        self::printSuccess('Database configurations finished with success .');

        self::tblsetup();
        self::printSuccess('Tables created, default theme app_v1 set up ..');

        self::printSuccess('Installation process finished with success.');
    }

    public static function update_dbfile($db_sourcefile, $server_addr, $db_name, $user_name, $pass_word)
    {
        // Use regex to replace the new variable names
        $patterns = [
            '/private static string \$morphDbHost = \'[^\']*\';/',
            '/private static string \$morphDbName = \'[^\']*\';/',
            '/private static string \$morphDbUser = \'[^\']*\';/',
            '/private static string \$morphDbPassword = \'[^\']*\';/'
        ];
        $replacements = [
            "private static string \$morphDbHost = '$server_addr';",
            "private static string \$morphDbName = '$db_name';",
            "private static string \$morphDbUser = '$user_name';",
            "private static string \$morphDbPassword = '$pass_word';"
        ];
        return preg_replace($patterns, $replacements, $db_sourcefile);
    }

    public static function tblsetup()
    {
        include self::getProjectRoot() . '/Base/Engine/Database/Database.php';
        $db = new \Morphine\Base\Engine\Database\Database();

        $db->unsafeQuery("DROP TABLE IF EXISTS `themes`");
        $db->unsafeQuery("CREATE TABLE `themes` (
                              `id` int NOT NULL,
                              `theme_path` varchar(45) DEFAULT NULL,
                              `theme_title` varchar(45) DEFAULT NULL,
                              `active` varchar(45) DEFAULT NULL,
                              `time` varchar(45) DEFAULT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
        $db->unsafeQuery("INSERT INTO `themes` VALUES (1,'application/themes/app_v1','app_v1','1',NULL)");
    }

    public static function package()
    {
        $build_config = [
            'compress_assets' => false,
            'include_wizard' => false,
            'wizard_dirname' => '',
            'wizard_sql_dump' => '',
            'project_name' => '',
        ];

        self::printInfo("Packaging your Morphine Application...");

        if (!is_dir(self::getProjectRoot() . '/build')) mkdir(self::getProjectRoot() . '/build');
        if (!is_dir(self::getProjectRoot() . '/build')) {
            self::printWarning("Couldn't create './base/build/' directory. Please create it manually then try again.");
            return false;
        }

        compress_question:
        self::printQuestion("Do you want to compress (minify) CSS/JS assets? [y/n] ");
        $answer = strtolower(trim(self::gets(2)));
        if ($answer === 'y') $build_config['compress_assets'] = true;
        elseif ($answer !== 'n') goto compress_question;

        wizard_question:
        self::printQuestion("Do you want to include an installation wizard? [y/n] ");
        $answer = strtolower(trim(self::gets(2)));
        if ($answer === 'y') {
            $build_config['include_wizard'] = true;

            self::printQuestion("Specify a folder name for the wizard (e.g., 'installer'): ");
            $wizard_name = trim(self::gets());
            $build_config['wizard_dirname'] = preg_replace('/[^a-zA-Z0-9_\-]/', '', $wizard_name);

            $sql = self::generate_sql_dump();
            if ($sql === false) {
                self::printFail("Aborting packaging due to failed SQL dump.");
                return false;
            }

            $build_config['wizard_sql_dump'] = base64_encode($sql); // Store in memory
            self::printSuccess("SQL dump generated from live database.");
        } elseif ($answer !== 'n') goto wizard_question;

        name_question:
        self::printQuestion("Enter a unique name for the packaged build (no extension): ");
        $project_name = trim(self::gets());
        if (file_exists(self::getProjectRoot() . "/build/{$project_name}.zip")) {
            self::printWarning("⚠️ A build with that name already exists.");
            goto name_question;
        }
        $build_config['project_name'] = $project_name;

        self::printInfo("Validating .htaccess integrity...");

        $htaccess_path = realpath(self::getProjectRoot() . '/.htaccess');

        if (!$htaccess_path || !file_exists($htaccess_path)) {
            self::printFail("⚠️ Missing .htaccess file. This file is required by Morphine to route requests properly.");
            return false;
        }

        $htaccess_content = trim(file_get_contents($htaccess_path));
        $expected_checksum = 'b5e3e331ef6e456e2140a7987afaa117';
        $current_checksum = md5($htaccess_content);

        // Validate checksum
        if ($current_checksum !== $expected_checksum) {
            self::printWarning("⚠️ Detected a modification in the .htaccess file.");
            confirm_htaccess:
            self::printQuestion("Do you wish to continue using the modified .htaccess file? [y/n]: ");
            $response = strtolower(trim(self::gets(3)));
            if ($response !== 'y') {
                self::printFail("❌ Aborted: Untrusted .htaccess file detected.");
                return false;
            }
        }

        $htaccess_for_zip = $htaccess_content;

        if ($build_config['include_wizard']) {
            $wizard_dir = trim($build_config['wizard_dirname'], '/');

            $htaccess_override = <<<HTACCESS
        RewriteEngine On
        
        # Allow static assets
        RewriteCond %{REQUEST_URI} \.(jpg|jpeg|png|jpeg|webp|svg|ico|gif|mp4|webm|pdf|doc|docx|ppt|pptx|avi|3gp|bmp|psd|mp3|aac|ogg|wav|zip|rar|7z|tar|gz)$ [NC]
        RewriteRule .* - [L]
        
        # Allow access to wizard directory
        RewriteCond %{REQUEST_URI} ^/{$wizard_dir}/ [NC]
        RewriteRule .* - [L]
        
        # Redirect everything else to wizard
        RewriteRule ^.*$ /{$wizard_dir}/ [L]
        HTACCESS;

            $htaccess_for_zip = $htaccess_override;
            self::printSuccess(".htaccess overridden in build to redirect users to /{$wizard_dir}/");
        } else {
            self::printInfo(".htaccess retained unmodified in the build (no wizard included).");
        }

        self::printInfo("Sanitizing Database.php credentials for ZIP only...");

        $db_path = realpath(self::getProjectRoot() . '/Base/Engine/Database/Database.php');
        if (!$db_path || !file_exists($db_path)) {
            self::printFail("Database.php not found. Aborting package for safety.");
            return false;
        }

        $original_db_content = file_get_contents($db_path);

        $patterns = [
            '/private static string \$morphDbHost\s*=\s*\'[^\']*\';/',
            '/private static string \$morphDbName\s*=\s*\'[^\']*\';/',
            '/private static string \$morphDbUser\s*=\s*\'[^\']*\';/',
            '/private static string \$morphDbPassword\s*=\s*\'[^\']*\';/'
        ];
        $replacements = [
            "    private static string \$morphDbHost = '[DB_HOST]';",
            "    private static string \$morphDbName = '[DB_NAME]';",
            "    private static string \$morphDbUser = '[DB_USER]';",
            "    private static string \$morphDbPassword = '[DB_PASS]';"
        ];
        $sanitized_content = preg_replace($patterns, $replacements, $original_db_content, -1, $count);

        if ($count < 4) {
            self::printFail("Sanitization failed. Only {$count}/4 patterns replaced. Check Database.php structure.");
            echo "DEBUG Preview:\n" . substr($sanitized_content, 0, 400) . "\n";
            return false;
        }

        if (strpos($sanitized_content, '[DB_PASS]') !== false) {
            //self::printSuccess("Database.php placeholders inserted correctly.");
        } else {
            self::printFail("Sanitization failed. Please verify the Database.php structure.");
            self::printSuccess("Database.php added to build ZIP as is, change credentials manually.");
        }

        $index_path = realpath(self::getProjectRoot() . '/index.php');
        if (!$index_path || !file_exists($index_path)) {
            self::printFail("Missing index.php file in project root.");
            return false;
        }

        $index_content = file_get_contents($index_path);

        $index_content = preg_replace(
            "/ini_set\('display_errors',\s*'1'\);/i",
            "ini_set('display_errors', '0');",
            $index_content
        );
        $index_content = preg_replace(
            "/ini_set\('display_startup_errors',\s*'1'\);/i",
            "ini_set('display_startup_errors', '0');",
            $index_content
        );

        self::printSuccess("index.php display errors disabled in packaged build.");

        $compressed_assets = [];
        if ($build_config['compress_assets']) {
            $compressed_assets = self::collect_compressed_assets();
            self::printSuccess("Compressed all CSS/JS assets.");
        }


        $zip = new ZipArchive();
        $zip_path = self::getProjectRoot() . "/build/{$build_config['project_name']}.zip";
        if(!file_exists(self::getProjectRoot() . '/build')) mkdir(self::getProjectRoot() . '/build');
        if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            self::printFail("Failed to create the build ZIP archive.");
            return false;
        }

        $excluded_dirs = ['dump', 'build', '.git', '.idea'];
        self::add_folder_to_zip(self::getProjectRoot(), $zip, '', $excluded_dirs);
        $zip->addFromString('.htaccess', $htaccess_for_zip);
        $zip->addFromString('Base/Engine/Database/Database.php', $sanitized_content);
        $zip->addFromString('index.php', $index_content);
        foreach ($compressed_assets as $path => $minified_content) {
            $zip->addFromString($path, $minified_content);
        }

        if ($build_config['include_wizard']) {

            $wizard_php = base64_decode(CliResources::$wizard_html);
            $wizard_sql = base64_decode($build_config['wizard_sql_dump']);

            $wizard_folder = $build_config['wizard_dirname'];
            $zip->addFromString($wizard_folder . '/index.php', $wizard_php);
            $zip->addFromString($wizard_folder . '/schema.sql', $wizard_sql);

            self::printSuccess("Wizard and SQL added to build.");
        }
        else
        {
            $wizard_sql = base64_decode($build_config['wizard_sql_dump']);
            $zip->addFromString('schema.sql', $wizard_sql);
            self::printSuccess(".sql file added to build main directory.");
        }



        $zip->close();
        self::printSuccess("✅ Project successfully packaged: build/{$build_config['project_name']}.zip");
    }

    public static function collect_compressed_assets(): array
    {
        $compressed = [];

        $base_path = realpath(self::getProjectRoot());
        $asset_folders = [
            '/application/assets',
            '/application/themes'
        ];

        foreach ($asset_folders as $folder) {
            $full_path = $base_path . $folder;

            if (!is_dir($full_path)) continue;

            // Handle theme assets
            if ($folder === '/application/themes') {
                $themes = scandir($full_path);
                foreach ($themes as $theme) {
                    if ($theme === '.' || $theme === '..') continue;
                    $theme_asset_path = "$full_path/$theme/assets";
                    if (is_dir($theme_asset_path)) {
                        $theme_zip_path = "application/themes/{$theme}/assets";
                        $compressed += self::scan_and_compress_assets($theme_asset_path, $theme_zip_path);
                    }
                }
            } else {
                $relative_zip_path = ltrim($folder, '/');
                $compressed += self::scan_and_compress_assets($full_path, $relative_zip_path);
            }
        }

        return $compressed;
    }

    public static function scan_and_compress_assets(string $folder_path, string $relative_zip_base): array
    {
        $compressed = [];

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder_path));

        foreach ($rii as $file) {
            if ($file->isDir()) continue;

            $file_path = $file->getPathname();
            $ext = pathinfo($file_path, PATHINFO_EXTENSION);
            if (!in_array($ext, ['css', 'js'])) continue;

            $original = file_get_contents($file_path);
            $minified = ($ext === 'css') ? self::minify_css($original) : self::minify_js($original);

            // Compute ZIP path
            $sub_path = substr($file_path, strlen($folder_path) + 1);
            $zip_path = $relative_zip_base . '/' . str_replace('\\', '/', $sub_path); // normalize slashes

            $compressed[$zip_path] = $minified;
        }

        return $compressed;
    }

    public static function minify_css($input)
    {
        $input = preg_replace('!/\*.*?\*/!s', '', $input);
        $input = preg_replace('/\n\s*\n/', "\n", $input);
        $input = preg_replace('/[\n\r \t]/', '', $input);
        $input = preg_replace('/ +/', ' ', $input);
        $input = preg_replace('/ ?([,:;{}]) ?/', '$1', $input);
        return trim($input);
    }

    public static function minify_js($input)
    {
        $input = preg_replace('!/\*.*?\*/!s', '', $input);
        $input = preg_replace('/\s*\/\/.*$/m', '', $input);
        $input = preg_replace('/[\n\r\t]/', '', $input);
        $input = preg_replace('/ +/', ' ', $input);
        return trim($input);
    }

    public static function generate_sql_dump()
    {
        include_once self::getProjectRoot() . '/Base/Engine/Database/Database.php';
        $db = new \Morphine\Base\Engine\Database\Database();

        $tables = $db->unsafeQuery("SHOW TABLES");
        if (!$tables || !is_array($tables)) {
            print($tables."\n");
            self::printFail("Failed to fetch table list for SQL dump.");
            return false;
        }

        $schema = '';
        foreach ($tables as $table_row) {
            $table_name = array_values($table_row)[0];

            // Get CREATE TABLE statement
            $create_stmt = $db->unsafeQuery("SHOW CREATE TABLE `$table_name`");
            if (!isset($create_stmt[0]['Create Table'])) continue;

            $schema .= "-- ----------------------------\n";
            $schema .= "-- Table structure for `$table_name`\n";
            $schema .= "-- ----------------------------\n";
            $schema .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $schema .= $create_stmt[0]['Create Table'] . ";\n\n";

            // Dump table data
            $rows = $db->unsafeQuery("SELECT * FROM `$table_name`");
            foreach ($rows as $row) {
                $columns = array_map(fn($v) => "`$v`", array_keys($row));
                $values  = array_map(fn($v) => $v === null ? 'NULL' : ("'" . addslashes($v) . "'"), array_values($row));
                $schema .= "INSERT INTO `$table_name` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
            }

            $schema .= "\n";
        }

        return $schema;
    }

    public static function add_folder_to_zip($folder, ZipArchive $zip, $subfolder = '', array $excluded_dirs = [])
    {
        $folder = rtrim($folder, '/');
        $subfolder = ltrim($subfolder, '/');

        $items = scandir($folder . '/' . $subfolder);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $full_path = $folder . '/' . $subfolder . '/' . $item;
            $relative_path = $subfolder === '' ? $item : $subfolder . '/' . $item;

            // Exclude specific folders
            $should_exclude = false;
            foreach ($excluded_dirs as $excluded) {
                if (substr($relative_path, 0, strlen($excluded)) === $excluded) {
                    $should_exclude = true;
                    break;
                }
            }
            if ($should_exclude) continue;

            if (is_dir($full_path)) {
                $zip->addEmptyDir($relative_path);
                self::add_folder_to_zip($folder, $zip, $relative_path, $excluded_dirs);
            } else {
                $zip->addFile($full_path, $relative_path);
            }
        }
    }

    public static function create($arg)
    {
        switch (strtolower($arg)){
            case 'view':
                self::createView();
                break;
            case 'model':
                self::createModelOrOperation('model');
                break;
            case 'operation':
                self::createModelOrOperation('operation');
                break;
            default:
                self::printWarning('invalid create argument');
        }
    }

    public static function createView() {
        self::printInfo("Creating a new View in Morphine Framework.");

        self::printQuestion("Enter the theme name: ");
        $theme_name = trim(self::gets());

        if (empty($theme_name)) {
            self::printFail("Theme name cannot be empty.");
            return;
        }

        self::printWarning("Please enter the *class name* (CamelCase, case-sensitive)");
        self::printQuestion("Enter the View class name (e.g., UserDashboard): ");
        $view_class_name = trim(self::gets());

        if (empty($view_class_name)) {
            self::printFail("View class name cannot be empty.");
            return;
        }

        $view_dir_name = strtolower($view_class_name);
        $theme_view_dir = self::getProjectRoot() . "/Application/Themes/{$theme_name}/{$view_dir_name}";
        $tpl_file_path = "{$theme_view_dir}/{$view_dir_name}.tpl.html";
        $php_class_file_path = self::getProjectRoot() . "/Application/Views/{$view_class_name}.php";

        // Check for existing view directory
        if (is_dir($theme_view_dir)) {
            self::printWarning("A view directory already exists at: Application/Themes/{$theme_name}/{$view_dir_name}");
            return;
        }

        // Step 1: Create view directory under theme
        if (!mkdir($theme_view_dir, 0755, true)) {
            self::printFail("Failed to create directory: {$theme_view_dir}");
            return;
        }

        // Step 2: Create empty .tpl.html file (lowercase)
        if (file_put_contents($tpl_file_path, "<div>{PLACEHOLDER}</div>") === false) {
            self::printFail("Failed to create template file: Application/Themes/{$theme_name}/{$view_dir_name}/{$view_dir_name}.tpl.html");
            return;
        }
        self::printSuccess("Created template file: Application/Themes/{$theme_name}/{$view_dir_name}/{$view_dir_name}.tpl.html");


        if (!isset(CliResources::$view_class_base64)) {
            self::printFail("View class stub not found in cli.resources.php.");
            return;
        }

        $stub_raw = base64_decode(CliResources::$view_class_base64);
        $stub_with_name = str_replace('{ViewName}', $view_class_name, $stub_raw);

        // Step 4: Write PHP class file
        if (file_put_contents($php_class_file_path, $stub_with_name) === false) {
            self::printFail("Failed to write PHP class file: Application/Views/{$view_class_name}.php");
            return;
        }
        self::printSuccess("Created View class: Application/Views/{$view_class_name}.php");

        // Final note to user
        self::printInfo("View '{$view_class_name}' has been created inside theme '{$theme_name}'.");
        self::printWarning("Reminder: This view is not yet bound to a surface. You must bind it manually.");
    }

    public static function createModelOrOperation($type)
    {
        $type = strtolower(trim($type));
        if (!in_array($type, ['model', 'operation'])) {
            self::printFail("Invalid type '$type'. Only 'model' or 'operation' are supported.");
            return;
        }

        $type_ucfirst = ucfirst($type);
        $dir = $type === 'model' ? 'models' : 'operations';
        $stub_key = $type === 'model' ? 'model_class_base64' : 'operation_class_base64';

        self::printInfo("Creating a new $type_ucfirst in Morphine Framework.");
        self::printWarning("Please enter the {$type_ucfirst} class name in CamelCase (case-sensitive).");

        self::printQuestion("Enter the $type_ucfirst class name: ");
        $class_name = trim(self::gets());

        if (empty($class_name)) {
            self::printFail("$type_ucfirst class name cannot be empty.");
            return;
        }

        $target_file_path = self::getProjectRoot() . "/Application/{$dir}/{$class_name}.php";

        // Step 1: Check if the file already exists
        if (file_exists($target_file_path)) {
            self::printWarning("$type_ucfirst class already exists at: application/{$dir}/{$class_name}.php");
            return;
        }

        if (!isset(CliResources::$$stub_key)) {
            self::printFail("Stub for $type_ucfirst not found in cli.resources.php. Expected key: \${$stub_key}");
            return;
        }

        $stub_encoded = CliResources::$$stub_key;
        $stub_raw = base64_decode($stub_encoded);
        $stub_with_class = str_replace('{ClassName}', $class_name, $stub_raw);

        // Step 3: Create the file
        if (file_put_contents($target_file_path, $stub_with_class) === false) {
            self::printFail("Failed to write $type_ucfirst class file: Application/{$dir}/{$class_name}.php");
            return;
        }

        self::printSuccess("{$type_ucfirst} class '{$class_name}' created successfully at: Application/{$dir}/{$class_name}.php");
    }

    public static function showChannels()
    {
        \Morphine\Base\Events\Dispatcher\Channels::init();
        $channels = \Morphine\Base\Events\Dispatcher\Channels::$channels;

        if (empty($channels)) {
            self::printWarning("No channels are defined." . PHP_EOL);
            return;
        }

        echo "Morphine Channels Overview:" . PHP_EOL . PHP_EOL;

        foreach ($channels as $channelName => $surfaces) {
            echo "╔═ Channel: {$channelName}" . PHP_EOL;

            $surfaceNames = array_keys($surfaces);
            $total = count($surfaceNames);
            foreach ($surfaceNames as $index => $surfaceName) {
                $prefix = "║   └─";
                echo "{$prefix} Surface: {$surfaceName}" . PHP_EOL;
            }

            echo "╚" . str_repeat("═", 47) . PHP_EOL . PHP_EOL;
        }

        self::printSuccess("Total Channels: " . count($channels) . PHP_EOL);
    }

    public static function showSurfaceDetails($target)
    {
        \Morphine\Base\Events\Dispatcher\Channels::init();
        $channels = \Morphine\Base\Events\Dispatcher\Channels::$channels;

        if (strpos($target, ':') === false) {
            self::printFail("Invalid format. Use: surface channel:surface");
            return;
        }

        list($channel, $surface) = explode(':', $target, 2);

        if (!isset($channels[$channel])) {
            self::printFail("Channel '$channel' not found.");
            return;
        }

        $channel = trim($channel);
        $surface = trim($surface);

        if (!isset($channels[$channel][$surface])) {
            self::printFail("Surface '$surface' not found in channel '$channel'.");
            return;
        }

        $data = $channels[$channel][$surface];

        echo "\nSurface: \033[1m$channel:$surface\033[0m\n";
        echo "═════════════════════════════════════════════════════\n";

        echo "  \033[1;34mAccepted Methods:\033[0m    " . implode(', ', $data['accepted_methods'] ?? ['-']) . "\n";
        echo "  \033[1;34mAccess Control:\033[0m      " . implode(', ', $data['access_control'] ?? ['-']) . "\n";

        if (isset($data['operation'])) {
            echo "  \033[1;34mOperation:\033[0m           {$data['operation']}\n";
        }

        echo "  \033[1;34mExceptions:\033[0m\n";
        if (!empty($data['exception'])) {
            foreach ($data['exception'] as $err => $handler) {
                $viewLabel = preg_match('/^R->/', $handler) ? $handler : "View: $handler";
                echo "    - $err => $viewLabel\n";
            }
        } else {
            echo "    (none)\n";
        }

        echo "  \033[1;34mParameters:\033[0m\n";

        $required = $data['parameters']['required'] ?? [];
        $optional = $data['parameters']['optional'] ?? [];

        echo "    Required:\n";
        if (!empty($required)) {
            foreach ($required as $type => $names) {
                foreach ($names as $n) {
                    $formatted = self::formatParam($type, $n);
                    echo "      • $formatted\n";
                }
            }
        } else {
            echo "      (none)\n";
        }

        echo "    Optional:\n";
        if (!empty($optional)) {
            foreach ($optional as $type => $names) {
                foreach ($names as $n) {
                    $formatted = self::formatParam($type, $n);
                    echo "      • $formatted\n";
                }
            }
        } else {
            echo "      (none)\n";
        }

        echo "═════════════════════════════════════════════════════\n";
    }

    public static function formatParam($type, $name)
    {
        // Special format if it's a validator-like type
        if (strpos($type, ':') !== false) {
            list($base, $validator) = explode(':', $type, 2);
            if (!empty($validator)) {
                return "$base:$validator() <= $name";
            }
        }
        return "$type <= $name";
    }

    public static function showChannel($channel_name)
    {
        \Morphine\Base\Events\Dispatcher\Channels::init();
        $channels = \Morphine\Base\Events\Dispatcher\Channels::$channels;

        $channel_name = trim($channel_name);

        if (!isset($channels[$channel_name])) {
            self::printFail("Channel '$channel_name' does not exist.");
            return;
        }

        $surfaces = array_keys($channels[$channel_name]);

        echo "\nChannel: \033[1m$channel_name\033[0m\n";
        echo "═════════════════════════════════════════════════════\n";

        foreach ($surfaces as $s) {
            echo "  • $s\n";
        }

        echo "═════════════════════════════════════════════════════\n";
        self::printSuccess("Total Surfaces: " . count($surfaces));
    }

    public static function traceView($view=false)
    {
        self::printInfo("Trace View Reference in Morphine");

        if(!$view)
        {
            self::printWarning("Please enter the View name (case-insensitive): ");
            self::printQuestion("Enter view name to trace: ");
            $view = trim(self::gets());

            if (empty($view)) {
                self::printFail("View name cannot be empty.");
                return;
            }
        }
        $view = trim($view);

        $basePath = realpath(self::getProjectRoot() . '/Application');
        $viewDir = $basePath . '/Views';
        $themesDir = $basePath . '/Themes';

        $lc = strtolower($view);
        $actualClassFile = null;

        foreach (glob("$viewDir/*.php") as $file) {
            if (strtolower(basename($file, '.php')) === $lc) {
                $actualClassFile = basename($file);
                break;
            }
        }

        if ($actualClassFile) {
            self::printSuccess("View class file found: Application/Views/{$actualClassFile}");
        } else {
            self::printWarning("View class file not found.");
        }

        $foundTemplate = false;
        foreach (glob("$themesDir/*", GLOB_ONLYDIR) as $themePath) {
            $themeName = basename($themePath);
            $tplFolder = "$themePath/$lc";
            $tplFile = "$tplFolder/{$lc}.tpl.html";

            if (file_exists($tplFile)) {
                self::printSuccess("Template found: Application/Themes/{$themeName}/{$lc}/{$lc}.tpl.html");
                $foundTemplate = true;
            }
        }

        if (!$foundTemplate) {
            self::printWarning("View template not found in any theme.");
        }

        $channelFile = realpath(self::getProjectRoot() . '/Base/Events/Dispatcher/Channels.php');
        if (!file_exists($channelFile)) {
            self::printFail("Channels.php not found.");
            return;
        }

        require_once $channelFile;
        \Morphine\Base\Events\Dispatcher\Channels::init();
        $channels = \Morphine\Base\Events\Dispatcher\Channels::$channels;

        $matches = [];

        foreach ($channels as $channel => $surfaces) {
            foreach ($surfaces as $surface => $data) {
                $match = false;

                if (isset($data['display']) && strtolower($data['display']) === $lc) {
                    $matches[] = [
                        'channel' => $channel,
                        'surface' => $surface,
                        'type' => 'display'
                    ];
                    continue;
                }

                // Match against exceptions
                if (isset($data['exception']) && is_array($data['exception'])) {
                    foreach ($data['exception'] as $ex => $target) {
                        // Match R->view or plain view string
                        if (
                            preg_match('/^R->(.+)$/', $target, $m) && strtolower($m[1]) === $lc ||
                            (strpos($target, '->') === false && strtolower($target) === $lc)
                        ) {
                            $matches[] = [
                                'channel' => $channel,
                                'surface' => $surface,
                                'type' => "exception ($ex)"
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($matches)) {
            self::printInfo("Referenced in surfaces:");
            foreach ($matches as $m) {
                echo "  => {$m['channel']}:{$m['surface']}  →  ({$m['type']})" . PHP_EOL;
            }
        } else {
            self::printWarning("No references found in channels.");
        }

        echo str_repeat("═", 55) . PHP_EOL;
    }


    public static function checkBrokenViews()
    {
        $basePath = realpath(self::getProjectRoot());
        $viewClassesPath = "$basePath/Application/Views";
        $themesPath = "$basePath/Application/Themes";
        $broken = [];

        if (!file_exists($channelFile)) {
            self::printFail("Channels.php not found.");
            return;
        }

        \Morphine\Base\Events\Dispatcher\Channels::init();
        $channels = \Morphine\Base\Events\Dispatcher\Channels::$channels;

        // View references as: viewname => [ [channel, surface, source] ]
        $referencedViews = [];

        foreach ($channels as $channel => $surfaces) {
            foreach ($surfaces as $surface => $surfaceData) {
                if (isset($surfaceData['display'])) {
                    $view = $surfaceData['display'];
                    $referencedViews[strtolower($view)][] = [$channel, $surface, 'display'];
                }

                if (!empty($surfaceData['exception'])) {
                    foreach ($surfaceData['exception'] as $ex => $val) {
                        if (preg_match('/^R->(.+)$/', $val, $m)) {
                            $view = $m[1];
                            $referencedViews[strtolower($view)][] = [$channel, $surface, "exception ($ex)"];
                        } elseif (strpos($val, '->') === false) {
                            $view = $val;
                            $referencedViews[strtolower($view)][] = [$channel, $surface, "exception ($ex)"];
                        }
                    }
                }
            }
        }

        $existingViewFiles = [];
        foreach (glob("$viewClassesPath/*.php") as $file) {
            $existingViewFiles[strtolower(basename($file, ".php"))] = basename($file);
        }

        $existingTemplates = [];
        foreach (glob("$themesPath/*", GLOB_ONLYDIR) as $themeFolder) {
            foreach (glob("$themeFolder/*/*.tpl.html") as $tpl) {
                $dir = basename(dirname($tpl));
                $tplname = basename($tpl, '.tpl.html');
                if ($dir === $tplname) {
                    $existingTemplates[strtolower($tplname)] = true;
                }
            }
        }

        self::printInfo("Checking for broken view references...");
        echo str_repeat("═", 60) . PHP_EOL;

        foreach ($referencedViews as $lcView => $refs) {
            $existsInViews = isset($existingViewFiles[$lcView]);
            $existsInThemes = isset($existingTemplates[$lcView]);

            if (!$existsInViews || !$existsInThemes) {
                $broken[] = $lcView;
                echo "Broken View: {$lcView}" . PHP_EOL;

                foreach ($refs as [$channel, $surface, $source]) {
                    echo "   • Referenced in: Channel: {$channel} | Surface: {$surface} | Source: {$source}" . PHP_EOL;
                }

                if (!$existsInViews) {
                    echo "   [!] Missing class file: /Application/Views/{$lcView}.php" . PHP_EOL;
                }
                if (!$existsInThemes) {
                    echo "   [!] Missing template: /Application/Themes/*/{$lcView}/{$lcView}.tpl.html" . PHP_EOL;
                }

                echo str_repeat("-", 50) . PHP_EOL;
            }
        }

        if (empty($broken)) {
            self::printSuccess("No broken view references found.");
        } else {
            self::printWarning("Total broken views: " . count($broken));
        }

        echo str_repeat("═", 60) . PHP_EOL;
    }

    public static function traceop($inputOp)
    {
        self::printInfo("Trace an Operation");

        $inputOp = trim($inputOp);

        $opLower = strtolower($inputOp);
        $operationFile = realpath(self::getProjectRoot() . '/Base/Events/Operation.php');

        if (!$operationFile || !file_exists($operationFile)) {
            self::printFail("Operation.php not found.");
            return;
        }

        require_once self::getProjectRoot() . '/Base/Events/events.php';
        require_once $operationFile;

        if (!class_exists(\Morphine\Base\Events\Operation::class)) {
            self::printFail("Class Morphine\\Base\\Events\\Operation not found in Operation.php.");
            return;
        }

        $opMethods = get_class_methods(\Morphine\Base\Events\Operation::class);
        $matchedMethod = null;

        foreach ($opMethods as $method) {
            if (strtolower($method) === $opLower) {
                $matchedMethod = $method; // get the real case-sensitive name
                break;
            }
        }

        if (!$matchedMethod) {
            self::printWarning("No method named '$inputOp' found in Operation.php.");
        } else {
            self::printSuccess("Found operation: $matchedMethod in Operation.php");
        }

        $channelFile = realpath(self::getProjectRoot() . '/Base/Events/Dispatcher/Channels.php');
        if (!file_exists($channelFile)) {
            self::printFail("Channels.php not found.");
            return;
        }

        \Morphine\Base\Events\Dispatcher\Channels::init();
        $channels = \Morphine\Base\Events\Dispatcher\Channels::$channels;

        $usages = [];

        foreach ($channels as $channel => $surfaces) {
            foreach ($surfaces as $surface => $data) {
                if (isset($data['operation']) && strtolower($data['operation']) === $opLower) {
                    $usages[] = [$channel, $surface];
                }
            }
        }

        echo PHP_EOL;
        echo "Operation Usage in Channels:" . PHP_EOL;
        echo str_repeat("═", 50) . PHP_EOL;

        if (!empty($usages)) {
            foreach ($usages as [$channel, $surface]) {
                echo "• Channel: $channel | Surface: $surface" . PHP_EOL;
            }
        } else {
            echo "This operation is not referenced in any channel surface." . PHP_EOL;
        }

        echo str_repeat("═", 50) . PHP_EOL;
    }

    public static function dump()
    {
        self::printInfo("Starting Database Dump...");

        $db_file = realpath(self::getProjectRoot() . '/Base/Engine/Database/Database.php');
        if (!file_exists($db_file)) {
            self::printFail("Database.php not found.");
            return;
        }

        $content = file_get_contents($db_file);

        preg_match('/private static string \$morphDbHost\s*=\s*\'([^\']+)\'/', $content, $host_match);
        preg_match('/private static string \$morphDbUser\s*=\s*\'([^\']+)\'/', $content, $user_match);
        preg_match('/private static string \$morphDbPassword\s*=\s*\'([^\']*)\'/', $content, $pass_match);
        preg_match('/private static string \$morphDbName\s*=\s*\'([^\']+)\'/', $content, $name_match);

        $host = $host_match[1] ?? null;
        $user = $user_match[1] ?? null;
        $pass = $pass_match[1] ?? null;
        $db   = $name_match[1] ?? null;

        if (!$host || !$user || !$db) {
            self::printFail("Failed to extract DB credentials from Database.php.");
            return;
        }

        $conn = @mysqli_connect($host, $user, $pass, $db);
        if (!$conn) {
            self::printFail("Failed to connect to database: " . mysqli_connect_error());
            return;
        }

        $dump_dir = self::getProjectRoot() . '/dump';
        if (!file_exists($dump_dir)) mkdir($dump_dir, 0755, true);

        $date = date("Y-m-d_H-i-s");
        $file_path = "$dump_dir/morphine_dump_$date.sql";

        $tables = [];
        $res = mysqli_query($conn, "SHOW TABLES");
        while ($row = mysqli_fetch_row($res)) {
            $tables[] = $row[0];
        }

        $sql = "-- Morphine DB Dump\n-- Date: $date\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";

            $create = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE `$table`"));
            $sql .= $create[1] . ";\n\n";

            $rows = mysqli_query($conn, "SELECT * FROM `$table`");
            while ($r = mysqli_fetch_assoc($rows)) {
                $vals = array_map(function ($val) use ($conn) {
                    return is_null($val) ? "NULL" : "'" . mysqli_real_escape_string($conn, $val) . "'";
                }, array_values($r));

                $sql .= "INSERT INTO `$table` VALUES (" . implode(",", $vals) . ");\n";
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($file_path, $sql);

        // 6. Done
        self::printSuccess("Dump completed.");
        self::printInfo("File saved to: dump/" . basename($file_path));
    }



    public static function clist()
    {
        self::printInfo("Morphine CLI Command List");
        echo str_repeat("═", 60) . PHP_EOL;

        $commands = [
            'install'       => 'Run the installation wizard',
            'pack'          => 'Package the application ',
            'create'        => 'Create a new View, Model, or Operation',
            'channels'      => 'List all channels and their surfaces',
            'channel'       => 'View a single channel and its surfaces',
            'surface'       => 'Show details for a specific surface',
            'traceview'     => 'Trace where a View is used across the app',
            'traceop'       => 'Trace where an Operation is referenced',
            'broken_views'    => 'Check for broken or missing view files',
            'dump'          => 'Export a full .sql database dump',
            'exit'          => 'Exit the CLI',
        ];

        foreach ($commands as $cmd => $desc) {
            printf("  %-15s →  %s\n", $cmd, $desc);
        }

        echo str_repeat("═", 60) . PHP_EOL;
    }
}
?>