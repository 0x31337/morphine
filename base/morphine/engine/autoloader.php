<?php
/**
 * This autoloader helps us invoke any object from any file/class that is being used by our Application
 * Without the need to explicitly include the php file of a class everytime it's being used in an outer scope
 * Putting in mind the structure organization of which one comes first, and the big number of files to include.
 * This gives us more clean space and 'memory' to develop functionalities without being afraid of inclusion issues.
 * More Details:
 * > php.net/manual/en/function.spl-autoload-register.php
 * ----------------------------------------------------------------------------------------------------------------
 *
 * @param $class_name : The subject class that's going to be loaded during runtime when needed.
 */
require 'common.php';

function morph_loader($class_name)
{
    $current_dir = getcwd();
    // Trailing slash is necessary
    $directories = array(
        $current_dir.'/application/models/',
        $current_dir.'/application/operations/',
        $current_dir.'/application/views',
        $current_dir.'/base/morphine/renders/',
        $current_dir.'/base/morphine/events/',
        $current_dir.'/base/morphine/events/dispatcher/',
        $current_dir.'/base/morphine/engine/',
        $current_dir.'/base/morphine/engine/database/',
        $current_dir.'/base/morphine/engine/security/',
        $current_dir.'/base/morphine/engine/websocket/'
    );


    foreach($directories as $directory)
    {
        /*
         * Check if the Class is being called from a namespace
         * This procedure will keep namespaces independent from filesystem structure
         * To give freedom on how and where the files are placed and modified
         * Summary:
         * > If you happen to change the filesystem structure, move a file ..etc
         * > You don't need to go and change the namespace throughout the source code
         * > All you need to do is to add the new directory in the $directories array above.
         */
        $cc = $class_name;

        if(strpos($class_name, '\\'))
        {
            $parts = explode('\\', $class_name);
            $class_name = end($parts);

            /*
             * Telling the autoloader to differ between Models and Operations
             * While preserving the filesystem rule. Using only the namespace
             */
            if(strpos($class_name, 'Models'))
            {
                $directory = $current_dir.'/application/models/';
            }
            elseif(strpos($class_name, 'Operations'))
            {
                $directory = $current_dir.'/application/operations/';
            }
        }

        if(file_exists($directory.$class_name . '.php'))
        {
            require_once($directory.$class_name . '.php');
            # If found in the first try, don't include the other class with same name.
            if(class_exists($cc))
            {
                return;
            }
        }
    }
}
spl_autoload_register('morph_loader');

require 'globals.php';