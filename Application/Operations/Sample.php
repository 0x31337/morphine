<?php

namespace Morphine\Application\Operations;


class Sample
{
    private \Morphine\Base\Engine\Database\Database $db;
    function __construct()
    {
        $this->db = AppGlobals::$DB;
    }
}
