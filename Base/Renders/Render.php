<?php
/**
 *  ################################################################################
 *  # Render Class responsible for rendering the appropriate pages to the end user #
 *  ################################################################################
 *
 *  > This class renders the appropriate pages ("Templates") to the end user out of
 *  > dynamic data, in response of triggered events. For more in-depth information,
 *  > please refer to the documentations under "Renders" section.
 */

namespace Morphine\Base\Renders;

use Morphine\Base\Engine\AppGlobals;
use Morphine\Base\Engine\Database;
use Morphine\Base\Events\Pages;
use Morphine\Base\Events\Display;
use Morphine\Base\Engine\Template;
use Morphine\Base\Engine\Assets;

class Render
{
    private string $theme_dir;
    private string $theme_title;
    private View $requested_view;
    private Database\Database $db;

    // Dependency Injection in the second param
    public function __construct(string $view_name, Pages $current_page, iterable $data)
    {
        $this->db = AppGlobals::$DB;
        $this->requested_view = new View($view_name);
        $this->prepare_view($current_page, $data);
        ($this->requested_view)();
        Display::$asset_call_once = false;
        $this->show_page();
        Display::$asset_call_once = true;
        return $this;
    }

    public function __invoke(string $view_name, Pages $current_page, iterable $data)
    {
        return new self($view_name, $current_page, $data);
    }

    private function prepare_view($current_page, $data)
    {
        $this->theme_title = $this->get_theme();
        $this->theme_dir = AppGlobals::$ThemesDir . $this->theme_title;

        $this->requested_view->set_current_page($current_page);
        $this->requested_view->set_req_data($data);
        $this->requested_view->set_theme_dir($this->theme_dir);
        $this->requested_view->resolve_view_object();
    }

    private function get_theme()
    {
        $this->db->select('*', 'themes', ['active' => 1]);
        while ($row = $this->db->exists()) {
            return $row['theme_title'];
        }
        return false;
    }

    private function show_page()
    {
        $template_base = $this->theme_dir . '/' . strtolower($this->requested_view->view_name);
        $view_main_template = $this->requested_view->views['main'];
        $template_path = $template_base . '/' . $view_main_template . '.html';
        if (file_exists($template_path)) {
            $template = new Template($template_path);
            $views_data = $this->requested_view->views_data ?? [];
            $children = $this->requested_view->views['children'];
            if (!empty($views_data['main'])) {
                try {
                    foreach ($views_data['main'] as $search => $replace) {
                        $template->assign($search, $replace);
                    }
                } catch (\Exception $e) {
                    die('lol');
                }
            }
            if (!empty($children)) {
                foreach ($children as $placeholder => $tpl_name) {
                    $template->renderBuffer(
                        $placeholder,
                        $template_base . '/' . $tpl_name . '.html',
                        $views_data[$tpl_name] ?? []
                    );
                }
            }
            $template->show();
            (new Assets($this->theme_title))->load($this->requested_view->view_name);
        } else {
            die('Template error: ' . htmlentities($template_path) . ' not found.');
        }
    }
}

// (new Render(
//     'categories',
//     new Pages(),
//     array('param'=>'value')
// ));