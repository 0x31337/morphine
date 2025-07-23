<?php


namespace Morphine\Application\Views;

use Morphine\Base\Renders\Handler;
use Morphine\Base\Engine\Common;

if (!class_exists(UnknownEvent::class))
{
    class UnknownEvent extends Handler
    {

        public function set_main_view()
        {
            $this->main_view = $this->view_basename.".tpl";
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

        }

        public function load_input_views()
        {

        }
    }
}