<?php


namespace Application\Views;

use Morphine\Renders\Handler;
use Morphine\Renders\View;

if(!class_exists(GuestFrame::class))
{
    class GuestFrame extends Handler
    {
        # Declaring data objects

        # Declaring data variables
        private $input;
        private $title = 'test';

        public function __construct(View $requested_view)
        {
            parent::__construct($requested_view);
        }

        // Declare the main html view
        public function set_main_view()
        {
            $this->main_view = $this->view_basename.".tpl";
        }

        // Declare partial html views
        public function set_partial_views()
        {
            $this->partial_views = array(

            );
        }

        // Use Models to load necessary data to be used in this view
        public function load_data_models()
        {

        }

        public function set_views_data()
        {
            $this->views_data = array(
                # Main view data assignments
                'main' => array(
                    'TITLE' => $this->title,
                    'WELCOME_TEXT' => __('It Works!')
                )
            );
        }

        // Render user inputs (text, password, file, radio ..etc)
        public function load_input_views()
        {

        }
    }
}