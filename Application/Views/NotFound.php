<?php

namespace Morphine\Application\Views;

use Morphine\Base\Engine\Common;
use Morphine\Base\Renders\Handler;
use Morphine\Base\Renders\View;

if (!class_exists(NotFound::class))
{
    class NotFound extends Handler
    {

        public function __construct(View $requested_view)
        {
            parent::__construct($requested_view);
        }

        public function set_main_view()
        {
            $this->main_view = $this->view_basename.".tpl";
        }

        public function set_partial_views()
        {
            $this->partial_views = array();
        }

        public function load_data_models()
        {

        }

        public function set_views_data()
        {
            $this->views_data = array(
                # Main view data assignments
                'main' => array(
                    'MESSAGE' => Common::echoSecure('This page you\'re looking for is not found.'),
                )
            );
        }

        public function load_input_views()
        {

        }
    }
}