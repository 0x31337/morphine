<?php
/**
 * Class Pages : Gets the current page iteration
 */
namespace Morphine\Base\Events;

use Morphine\Base\Engine\Common;

class Pages
{
    public int $current_page;
    public function view()
    {
        return $this->current_page = $this->getDefaultCurrentPage();
    }

    private function getDefaultCurrentPage():int
    {
        $req_page = Common::req('page');
        if (is_numeric($req_page))
        {
            return $req_page;
        }
        else
        {
            return 1;
        }
    }

    // Dev. Note: Any future additional security layers such as rate-limiting upon Pages will be done in this file.
}