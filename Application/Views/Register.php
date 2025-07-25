<?php


namespace Morphine\Application\Views;

use Morphine\Base\Renders\Handler;
use Morphine\Base\Renders\View;
use Morphine\Base\Engine\Common;

if(!class_exists(Register::class))
{
    class Register extends Handler
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
            $this->partial_views = array(

            );
        }

        public function load_data_models()
        {

        }

        public function set_views_data()
        {
            $this->views_data = array(
                'main' => array(
                    'PLACEHOLDER' => Common::echoSecure('It Works!'),
                )
            );
        }
        public function load_input_views()
        {

        }
    }
}