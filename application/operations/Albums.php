<?php

namespace Application\Operations;

if (!class_exists(Albums::class))
{
    class Albums /* extends someClass */
    {
        private \Morphine\Engine\Database $db;

        function __construct()
        {
            $this->db = $GLOBALS['DB'];
        }

        /*
         * Operation methods here
         */


    }
}