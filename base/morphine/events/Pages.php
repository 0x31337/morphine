<?php
/**
 * Class Pages : Gets the current page iteration
 * > A highly secured page getter class which performs advanced defence against crawlers and injections
 * > @todo: make dynamic page request parameters .
 * > @todo: detect bots crawling pages by time interval & captcha system .
 * > @todo: link to Security Class
 */
namespace Morphine\Events;

class Pages
{
    public int $current_page;
    public function view($view_name)
    {
        switch ($view_name)
        {
            // dummy View names, change this to suit your needs
            case 'people':
            case 'categories':
            case 'gallery':
            case 'notifications':
                return $this->current_page = $this->getDeafultCurrentPage();
        }
    }

    /**
     * Accepts both "GET" and "POST" requests (integer only) and returns the page number if any
     * @return int : current page number
     */
    private function getDeafultCurrentPage():int
    {
        $req_page = req('page');
        if (is_numeric($req_page))
        {
            return $req_page;
        }
        else
        {
            return 1;
        }
    }
}