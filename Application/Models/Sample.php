<?php

namespace Morphine\Application\Models;

if(!class_exists(Sample::class)) {
    class Sample /* or extends someClass */
    {
        private \morphine\Base\Engine\Database\Database $db;

        function __construct()
        {
            $this->db = AppGlobals::$DB;
        }

        /*
         * Model methods here
         */
    }
}
