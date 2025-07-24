<?php

namespace Morphine\Base\Renders;

class ConditionalView
{
    private string $conditional_tpl;
    private string $conditional_tpl_path;
    private object $view_object;
    private iterable $COND_;
    private array $result_view_array;
    private iterable $args;
    public bool $called;

    public function __construct(string $conditional_tpl, object $view_object)
    {
        // setting up ConditionalView preferences
        $this->called = false;
        $this->conditional_tpl = $conditional_tpl;
        $this->view_object = $view_object;
        $this->conditional_tpl_path = $this->view_object->theme_dir . '/' .
            strtolower($this->view_object->view_basename) . '/' .
            $this->view_object->view_basename . '.' . $conditional_tpl;
    }

    public function condition(array ...$args)
    {
        $this->result_view_array = []; // Flush for Iterable ConditionalView(s)
        array_map(fn($args) => $this->resolve_conditions($args), func_get_args());
        return $this->show_result_view();
    }

    public function resolve_conditions($conditions)
    {
        if (is_array($conditions) && !empty($conditions)) {
            $this->args = $conditions['args'];
            $this->read_conditional_tpl();
            switch ($conditions['o']) {
                case '==':
                    if ($conditions['args'][0] == $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
                case '===':
                    if ($conditions['args'][0] === $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
                case '!=':
                    if ($conditions['args'][0] != $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
                case '>':
                    if ($conditions['args'][0] > $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
                case '<':
                    if ($conditions['args'][0] < $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
                case '>=':
                    if ($conditions['args'][0] >= $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
                case '<=':
                    if ($conditions['args'][0] <= $conditions['args'][1]) {
                        $this->fetch_result($conditions['return']['true'] ?? $conditions['return']['false']);
                    } else {
                        if (isset($conditions['return']['false'])) {
                            $this->fetch_result($conditions['return']['false']);
                        }
                    }
                    break;
            }
            return $result ?? false;
        }
        return false;
    }

    private function read_conditional_tpl()
    {
        if (!file_exists($this->conditional_tpl_path . '.html')) {
            echo 'Template error (conditional): unable to find conditional file in ' .
                $this->view_object->view_basename;
            return false;
        }

        $conditional_view_html = file_get_contents($this->conditional_tpl_path . '.html');
        $this->COND_ = $this->get_conditional_chunks($conditional_view_html);
        return ($this->COND_) ? true : false;
    }

    private function get_conditional_chunks($html)
    {
        if (strtolower(preg_split('#\r?\n#', ltrim($html), 0)[0]) == '<!-- conditional !-->') {
            $html_chunks = [];
            $chunks_1 = explode('<!--[ START COND_', $html);
            foreach ($chunks_1 as $chk) {
                $sub_chk = explode(' ]-->', $chk);
                $C_number = (int) $sub_chk[0];
                $html_chunks['COND_' . $C_number] =
                    explode('<!--[ END COND_', $sub_chk[1] ?? $sub_chk[0])[0];
            }
            return $html_chunks;
        } else {
            echo 'Template popupmessage (conditional) : Please check the conditional tpl header at: ' .
                $this->view_object->view_basename;
            return false;
        }
    }

    private function fetch_result($cond_tag): void
    {
        if (strpos($cond_tag, ',')) {
            $cond_results = explode(',', $cond_tag);
            foreach ($cond_results as $cond_result) {
                $this->fetch_single_result($cond_result);
            }
        } else {
            $this->fetch_single_result($cond_tag);
        }
    }

    private function fetch_single_result($cond_tag)
    {
        $cond = $this->extract_conditional_callback($cond_tag);
        if (is_array($cond['params'])) {
            if (is_array($cond['params'])) {
                $callback = $cond['callback'];
                $this->result_view_array[] =
                    $this->view_object->$callback($this->COND_[$cond['cond_']], $cond['params']);
            }
        } elseif ($cond['callback']) {
            $callback = $cond['callback'];
            $this->result_view_array[] = $this->view_object->$callback();
        } else {
            $this->result_view_array[] = $this->COND_[$cond['cond_']];
        }
    }

    private function extract_conditional_callback($cond_result): iterable
    {
        if (strpos($cond_result, '}') > 1) {
            $callback = explode('[', $cond_result)[1];
            $callback = trim(explode('(', $callback)[0]);
            $params = explode($callback . "(", $cond_result)[1];
            $params = trim(explode(')', $params)[0]);
            if (strpos($params, '-')) {
                $params = array_map('trim', explode('-', $params));
            }
            $params = ($params != '') ? $this->assign_params($params) : false;
            $stripped_cond = explode('{', $cond_result)[1];
            $stripped_cond = trim(explode('}', $stripped_cond)[0]);
        }
        return [
            'callback' => $callback ?? false,
            'params' => $params ?? false,
            'cond_' => $stripped_cond ?? $cond_result,
        ];
    }

    private function assign_params($params)
    {
        $params = is_array($params) ? $params : [$params];
        foreach ($params as $key => $param) {
            $params[$key] = $this->args[$param];
        }
        return $params;
    }

    private function show_result_view()
    {
        if (!empty($this->result_view_array)) {
            $code = "";

            foreach ($this->result_view_array as $result) {
                $code .= $result;
            }
            return $code ?? false;
        }
        return false;
    }
}