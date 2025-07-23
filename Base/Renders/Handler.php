<?php


namespace Morphine\Base\Renders;

use Morphine\Base\Engine\AppGlobals;

if (!class_exists(Handler::class))
{
    abstract class Handler
    {
        private \Morphine\Base\Engine\Database\Database $db;
        public string $main_view;
        public iterable $partial_views;
        public iterable $views_data;
        public string $theme_dir;
        public int $current_page;
        public iterable $req_data;
        public string $view_basename;
        private \Morphine\Base\Renders\IterableView $iterable;


        abstract public function set_main_view();
        /**
         * Please note that there is a difference between iterable views and partial views:
         * Iterable views : Views that are going to be populated with dynamic data such as tables
         * Partial views : Views that are going to be filled with a fixed number of data
         * Conditional views : Views that contains in-file sub-views that can be triggered with a condition
         */
        abstract public function set_partial_views();
        abstract public function load_data_models();
        abstract public function set_views_data();
        abstract public function load_input_views();

        public function __construct(\Morphine\Base\Renders\View $requested_view)
        {
            $this->db = AppGlobals::$DB;
            $this->theme_dir = $requested_view->theme_dir;
            $this->current_page = $requested_view->current_page;
            $this->req_data = $requested_view->req_data;
            $this->view_basename = strtolower($this->get_view_file_prefix());
            $this->load_data_models();
            $this->set_partial_views();
            $this->load_input_views();
            $this->set_views_data();
            $this->set_main_view();
        }

        public function get_view_file_prefix()
        {
            try {
                $reflect = new \ReflectionClass($this);
                return $reflect->getShortName();
            } catch (\ReflectionException $e) {
                die("Morphine Views error: can't read classname");
            }
        }

        protected function normalize_cond_args($args)
        {
            return ['view'=>$args[0], 'values'=>$args[1]];
        }
    }
}