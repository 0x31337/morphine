<?php

namespace Morphine\Application\Operations;

if (!class_exists(Sample::class))
{
    class Sample /* extends someClass */
    {
        private \Morphine\Base\Engine\Database\Database $db;

        function __construct()
        {
            $this->db = AppGlobals::$DB;
        }

        /*
         * Operation methods here
         */


    }
}