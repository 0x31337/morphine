<?php

namespace Application\Models;

if(!class_exists(Albums::class)) {
    class Albums /* or extends someClass */
    {
        private \morphine\Engine\Database $db;

        function __construct()
        {
            $this->db = $GLOBALS['DB'];
        }

        /*
         * Model methods here
         */
    }
}
