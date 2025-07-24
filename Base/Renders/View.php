<?php
/**
 * Class View : Handles the view rendering process
 */
declare(strict_types=1);

namespace Morphine\Base\Renders;

use Morphine\Base\Engine\Common;

class View
{
    public string $view_name;
    public string $theme_dir;
    public iterable $views;
    public iterable $views_data;
    public iterable $req_data;
    public int $current_page;
    private ?object $view_object = null;
    public bool $call_once;

    public function __construct($view_name)
    {
        $this->call_once = false;
        $this->view_name = $view_name;
    }

    public function __invoke()
    {
        $this->resolve_view_object();
        $this->load_view_parts();
        $this->load_views_data();
    }

    public function resolve_view_object()
    {
        if (!$this->call_once) {
            $view_class = ucwords($this->view_name);
            $relative_classname = '\\Morphine\\Application\\Views\\' . $view_class;
            if (Common::viewsLoader($view_class)) {
                $this->view_object = new $relative_classname($this);
            }

            if ($this->view_object == null) {
                die('Morphine Error: There is no <i>View</i> named <b>' . htmlentities($view_class) . '</b> in your <u>application/views/</u> directory.');
            }
        }

        $this->call_once = true;
    }

    public function load_view_parts()
    {
        if ($this->view_object != null) {
            $this->views = [
                'main' => $this->view_object->main_view,
                'children' => $this->view_object->partial_views,
            ];
        }
    }

    public function load_views_data()
    {
        if (isset($this->view_object->views_data)) {
            $this->views_data = $this->view_object->views_data;
        }
    }

    public function set_current_page(\Morphine\Base\Events\Pages $current_page)
    {
        $this->current_page = (int) $current_page->view();
    }

    public function set_req_data(iterable $data)
    {
        $this->req_data = $data;
    }

    public function set_theme_dir($theme_dir)
    {
        $this->theme_dir = $theme_dir;
    }

    public function get_req_data()
    {
        return $this->req_data;
    }

    public function get_theme_dir()
    {
        return $this->theme_dir;
    }

    public function get_current_page()
    {
        return $this->current_page;
    }
}