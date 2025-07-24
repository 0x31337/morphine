<?php

namespace Morphine\Application\Models;

class Sample
{
    private \morphine\Base\Engine\Database\Database $db;

    function __construct()
    {
        $this->db = AppGlobals::$DB;
    }

}
