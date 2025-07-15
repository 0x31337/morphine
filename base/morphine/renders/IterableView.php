<?php


namespace Morphine\Renders;


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

    /**
     * Until now, we're using a technique for Iterable Conditions ;
     * In the future, we'll use no techniques, There will be a method to handle everything.
     * Leave this for later development, I am now drowning in an absolute busy-ness
     * Contact me for the whole idea.
     * @param callable $iteration_method
     * @param iterable $iterable
     * @return false|string
     */
    // commented-out to save memory
    /*public function iterate_condition(callable $iteration_method, iterable $iterable)
    {
        if (!file_exists($this->iterate_view.'.html'))
        {
            echo 'Template error (iteration): unable to find iterable file in '.$this->view_object->view_basename;
            return false;
        }

        $tpl = "";
        foreach ($iterable as $i)
        {
            $tpl .= call_user_func_array(
                [$this->view_object,$iteration_method],
                [$this->iterate_view, $i]
            );
        }
        return $tpl;
    }*/

    /**
     * Leave this for later development
     * @param $iteration_method
     * @param $arguments
     * @return mixed
     */
    /*public function __call($iteration_method, $arguments)
    {
        if ($iteration_method == 'iterate')
        {
            if (count($arguments) == 2 )
            {
                return call_user_func_array(array($this,'iterate_whole'), $arguments);
            }
        }
    }*/

    /**
     * The above 2 methods are not used in the BDR core yet, it's a substance of a future dev.
     * This is the only two methods that are placed in Morphine core without usage, Please be Cautious
     * while dealing with other parts of Morphine core.
     */
}