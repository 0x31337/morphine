<?php
/**
 * Functions Library file for Morph Command-Line Interface.
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if(!defined("STDIN")) {
    define("STDIN", fopen('php://stdin','rb'));
}

function print_success($message)
{
    print "[+] $message\n";
}
function print_fail($message)
{
    print "[-] $message\n";
}
function print_warning($message)
{
    print "[!] $message\n";
}
function print_info($message)
{
    print "[i] $message\n";
}
function print_question($message)
{
    print "[->] $message ";
}

function prompt()
{
    print "morph-shell> ";
}

function gets(int $length=255)
{
    return fread(STDIN, 255);
}

function install()
{
    print_info('Morphine framework installation process...');
    print_question('Enter your MySQL database server address :');
    $server_addr = gets();
    print_question('Enter your MySQL username : ');
    $user_name = gets();
    insert_password:
    print_question('Enter your MySQL Password : ');
    $pass_word = gets();
    if($pass_word == $user_name)
    {
        print_warning("You can't use the same string as username and password ! ");
        goto insert_password;
    }
    insert_dbname:
    print_question('Enter the database name you wish to use : ');
    $db_name = gets();
    if($db_name == $user_name)
    {
        print_warning("You can't use the same string as username and database name for security reasons !");
        goto insert_dbname;
    }
    if($db_name == $pass_word)
    {
        print_warning("You can't use the same string as password and database name for security reasons !");
        goto insert_dbname;
    }

    if(file_exists('../morphine/engine/database/Database.php'))
    {
        $databasefile = file_get_contents('../morphine/engine/database/Database.php');
    }
    else
    {
        print_warning("can't find database files, Morphine filesystem is corrupt, please check with original github clone");
        print_warning("|__ ( please note that morph shell should be inside /base/cli/ directory ) ");
        print_fail('Unable to install Morphine framework.');
        return false;
    }

    if(file_exists('../morphine/engine/globals.php'))
    {
        $globalsfile = file_get_contents('../morphine/engine/globals.php');
    }
    else
    {
        print_warning("can't find globals.php file, Morphine filesystem is corrupt, please check with original github clone");
        print_warning("|__ ( please note that morph shell should be inside /base/cli/ directory ) ");
        print_fail('Unable to install Morphine framework.');
        return false;
    }

    $output_dbfile = update_dbfile($databasefile, $server_addr, $db_name, $user_name, $pass_word);
    fwrite(fopen('../morphine/engine/database/Database.php', 'w'), $output_dbfile);
    print_success('Database configurations finished with success .');

    tblsetup();
    print_success('Tables created, default theme app_v1 set up ..');

    print_success('Installation process finished with success.');
}

function update_dbfile($db_sourcefile, $server_addr, $db_name, $user_name, $pass_word)
{
    $chk=explode( '\'', explode('private static $morph_db_host = ', $db_sourcefile)[1])[1];
    $p0 = strtr($db_sourcefile, [$chk => trim($server_addr)]);
    $chk=explode( '\'', explode('private static $morph_db_name = ', $db_sourcefile)[1])[1];
    $p1 = strtr($p0, [$chk => trim($db_name)]);
    $chk=explode( '\'', explode('private static $morph_db_user = ', $db_sourcefile)[1])[1];
    $p2 = strtr($p1, [$chk => trim($user_name)]);
    $chk=explode( '\'', explode('private static $morph_db_password = ', $db_sourcefile)[1])[1];
    $p3 = strtr($p2, [$chk => trim($pass_word)]);
    return $p3;
}

function tblsetup()
{
    include '../morphine/engine/database/Database.php';
    $db = new \Morphine\Engine\Database();

    $db->unsafe_query("DROP TABLE IF EXISTS `themes`");
    $db->unsafe_query("CREATE TABLE `themes` (
                              `id` int NOT NULL,
                              `theme_path` varchar(45) DEFAULT NULL,
                              `theme_title` varchar(45) DEFAULT NULL,
                              `active` varchar(45) DEFAULT NULL,
                              `time` varchar(45) DEFAULT NULL,
                              PRIMARY KEY (`id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    $db->unsafe_query("INSERT INTO `themes` VALUES (1,'application/themes/app_v1','app_v1','1',NULL)");
}

function pack()
{
    // 1- Creating build directory
    if(!is_dir('../build')) mkdir('../build');
    if(!is_dir('../build'))
    {
        print_warning("Couldn't create './base/build/ directory, please create it manually then try again. ");
        return false;
    }

    // 2- Asking whether to compress assets
    compress_question:
    print_question("Do you want to compress 'Minify' css/js assets ? [y/n]");
    $answer = gets(2);
    if(strtolower(trim($answer)) == 'y') { compress_assets(); }
    elseif(strtolower(trim($answer)) == 'n') {  }
    else { goto compress_question; }
    
    // 3- Asking whether to purge rogue

    // 4- Asking project name

    // 5- Zipping project to directory, after making sure it's not duplicate.

}

function compress_assets()
{
    return false;
}

function purge_rogue()
{
    return false;
}

function clist()
{
    print_info("Morph CLI commands list :");
    print "install \t pack \t exit\n";
}



function whatis($target)
{

}
?>