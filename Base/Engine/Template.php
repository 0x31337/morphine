<?php
/**
 *  ##############################################################
 *  # Template Class responsible for rendering pages to the user #
 *  ##############################################################
 *
 *  > This class is a system for rendering templates and views with dynamic data.
 *  > It allows us to customize the style and data being shown to the end user without
 *  > explicitly editing the HTML files. Also giving us space to create & change themes.
 * ------------------------------------------------------------------------------------
 *  > for more understanding of this templates system, please refer to the documentations
 *  > "Themes and Templates" page. If you're a developer, you'll also find there the manual
 *  > on how to create themes.
 */

namespace Morphine\Base\Engine;

class Template
{
    private array $assigned_values;
    private string $tpl;

    public function __construct($path = '', $isHTML = false)
    {
        if ($isHTML) {
            $this->tpl = $path;
        } elseif (!empty($path) && file_exists($path)) {
            $this->tpl = file_get_contents($path);
        } else {
            die('<b>Template error: </b> Couldn\'t load the template file.');
        }
    }

    public function assign($search, $replace)
    {
        $this->assigned_values[strtoupper($search)] = $replace;
    }

    public function renderBuffer(string $search, string $path, array $buffer_assigned_values)
    {
        if (file_exists($path)) {
            $buffer = file_get_contents($path);
            if (!empty($buffer_assigned_values)) {
                foreach ($buffer_assigned_values as $_search => $_replace) {
                    // strtr is 4 times faster than str_replace :D facts u know
                    $buffer = strtr($buffer, ['{' . strtoupper($_search) . '}' => $_replace]);
                    // $buffer = str_replace('{' . strtoupper($_search) . '}', $_replace, $buffer);
                }
            }
            $this->tpl = str_replace('[' . strtoupper($search) . ']', $buffer, $this->tpl);
            // unset($this->buffer);
        } else {
            die('<b>Template error: </b> Buffer file not found.');
        }
    }

    public function show($return_template = false)
    {
        if (!empty($this->assigned_values)) {
            foreach ($this->assigned_values as $search => $replace) {
                $this->tpl = strtr($this->tpl, ['{' . $search . '}' => $replace]);
                // $this->tpl = str_replace('{' . $search . '}', $replace, $this->tpl);
            }
        }
        if ($return_template === true) return $this->tpl;
        echo $this->tpl;
    }
}

/**
 * Usage :
 * require_once 'Template.php';
 * define('TEMPLATE_PATH', 'views');
 *
 * $template = new Template(TEMPLATES_PATH.'/profile/index.tpl.html');
 *
 * $template->assign('title', 'hello');
 * $template->assign('body', 'world');
 *
 * $assign_arr = array('name' => 'firstname', 'lastname' => 'lastname');
 * $template->renderBuffer('table_here', TEMPLATES_PATH.'/profile/settings.table.tpl.html', $assign_arr);
 *
 * $template->show();
 *
 * HTML:
 * title : {TITLE}
 * body : {BODY}
 * table: [TABLE_HERE]
 */
// simple as fuck