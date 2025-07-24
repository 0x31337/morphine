<?php

namespace Morphine\Application\Views;

use Morphine\Base\Engine\Common;
use Morphine\Base\Renders\Handler;
use Morphine\Base\Renders\View;

class NotFound extends Handler
{
    public function __construct(View $requested_view)
    {
        parent::__construct($requested_view);
    }

    public function set_main_view()
    {
        $this->main_view = $this->view_basename . ".tpl";
    }

    public function set_partial_views()
    {
        $this->partial_views = [];
    }

    public function load_data_models()
    {
    }

    public function set_views_data()
    {
        $this->views_data = [
            // Main view data assignments
            'main' => [
                'MESSAGE' => Common::echoSecure("This page you're looking for is not found."),
            ],
        ];
    }

    public function load_input_views()
    {
    }
}