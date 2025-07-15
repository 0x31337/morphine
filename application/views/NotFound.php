<?php


namespace Application\Views;

use Morphine\Renders\Handler;

if (!class_exists(NotFound::class))
{
    class NotFound extends Handler
    {

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

        }

        public function load_input_views()
        {

        }
    }
}