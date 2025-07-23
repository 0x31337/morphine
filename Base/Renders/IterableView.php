<?php


namespace Morphine\Base\Renders;


class IterableView
{
    private string $iterable_html_filename;
    private iterable $req_data;
    private int $page;
    private string $iterate_view;
    private object $view_object;

    # Dynamic Dependency Injection in the second parameter
    public function __construct(string $iterable_html_filename, object $view_object)
    {
        # setting up IterableView preferences
        $this->iterable_html_filename = $iterable_html_filename;
        $this->view_object = $view_object;
        $this->req_data = $this->view_object->req_data;
        $this->page = $this->view_object->current_page;
        $this->iterate_view = $this->view_object->theme_dir.'/'.
            strtolower($this->view_object->view_basename).'/'.
            $this->view_object->view_basename.'.'.$iterable_html_filename;
    }

    public function iterate(string $iteration_method, iterable $iterable, $keyVal=false)
    {
        if (!file_exists($this->iterate_view.'.html'))
        {
            echo 'Template error (iteration): unable to find iterable file in '.$this->view_object->view_basename;
            return false;
        }

        $tpl = "";
        foreach ($iterable as $key => $i)
        {
            $tpl .= call_user_func_array(
                [$this->view_object,$iteration_method],
                [$this->iterate_view, ($keyVal)?[$key,$i]:$i]
            );
        }
        return $tpl;
    }
}