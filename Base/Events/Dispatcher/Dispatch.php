<?php

namespace Morphine\Base\Events\Dispatcher;


use Morphine\Base\Events\Events;
use Morphine\Base\Events\Route;
use Morphine\Base\Engine\Common;
use Morphine\Base\Engine\AppGlobals;

if ( !class_exists(Dispatch::class))
{
    class Dispatch extends Events
    {
        public array $flagged;
        private array $channels;
        public array $current_channel;
        private bool $empty_req_available;
        private string $custom_display;
        private Surface $surface;
        private Trail $trail;

        public function __construct()
        {
            parent::$req_data = array();
        }

        public function __invoke()
        {
            Channels::init();
            $this->trail = Route::trace(parent::$URI, new Trail());
            $this->fetch_channel($this->trail->target);
            $this->surface = new Surface($this->current_channel, parent::$event);

            if($this->treat_surface())
            {
                # Surface treated without any exceptions
                parent::$req_data['event'] = parent::$event;
                $this->dispatch();
            }
             else
            {
                $this->check_redirection($this->custom_display);
                \Morphine\Base\Events\Display::_render($this->custom_display, parent::$req_data);
            }
        }

        private function dispatch()
        {
            if(!empty($this->surface->operation))
            {
                \Morphine\Base\Events\Operation::{$this->surface->operation}(parent::$req_data);
            }

            if(isset($this->surface->display))
            {
                $this->check_redirection($this->surface->display);
                \Morphine\Base\Events\Display::_render($this->surface->display, parent::$req_data);
            }
        }

        private function fetch_channel(string $target):void
        {
            if(Channels::exists($target))
            {
                $this->current_channel = Channels::get($target);
            }
            else
            {
                $this->current_channel = Channels::get('404');
            }
        }

        private function treat_surface()
        {
            $access_control = $this->surface->access_control??false;
            $surface_methods = $this->surface->accepted_methods??false;
            $surface_required_parameters = $this->surface->required_parameters??false;
            $surface_optional_parameters = $this->surface->optional_parameters??false;

            # Treat access control before any action
            if(!$this->access_control_validation($access_control))
            {
                $this->exception('E_ACCESS_CONTROL_FAILURE');
                return false;
            }

            # Make sure all required parameters are supplied
            if(isset($surface_required_parameters) && is_array($surface_required_parameters))
            {
                foreach ($surface_required_parameters as $rule => $expected_required_param)
                {
                    if (is_array($expected_required_param))
                    {
                        foreach($expected_required_param as $param)
                        {
                            if( false === $this->deliver_req_data($rule, $surface_methods, $param, true) )
                            {
                                return false;
                            }
                        }
                    }
                    else
                    {
                        if( false === $this->deliver_req_data($rule, $surface_methods, $expected_required_param, true) )
                        {
                            return false;
                        }
                    }
                }
            }


            # At this point, required parameters are checked and validated
            # Validating optional parameters' rules:

            if(isset($surface_optional_parameters) && is_array($surface_optional_parameters))
            {
                foreach ($surface_optional_parameters as $rule => $expected_optional_param)
                {
                    if (is_array($expected_optional_param))
                    {
                        foreach($expected_optional_param as $param)
                        {
                            if( false === $this->deliver_req_data($rule, $surface_methods, $param) )
                            {
                                return false;
                            }
                        }
                    }
                    else
                    {
                        if( false === $this->deliver_req_data($rule, $surface_methods, $expected_optional_param) )
                        {
                            return false;
                        }
                    }
                }
            }
            return true;
        }

        private function deliver_req_data($rule, $surface_methods, $expected_param, $required=false)
        {
            if ($request_param = $this->param_exists($surface_methods, $expected_param))
            {
                # Treat required parameters
                if ($this->rules_validation($rule, $request_param))
                {
                    parent::$req_data[$expected_param] = $request_param;
                }
                else
                {
                    # Rules validation error
                    $this->exception('E_RULES_FAILED');
                    return false;
                }
            }
            else
            {
                if($required)
                {
                    # Lack of required parameters
                    $this->exception('E_REQUIRED_PARAM_NOT_FOUND');
                    return false;
                }
            }
        }

        private function access_control_validation($surface_access_control_array)
        {
            if(isset($surface_access_control_array) && $surface_access_control_array !== false)
            {
                foreach ($surface_access_control_array as $access)
                {
                    switch (strtolower($access))
                    {
                        case 'guest':
                            if(Common::userId()) return false;
                            break;
                        case 'superadmin':
                            if(!Common::isSuperAdmin()) return false;
                            break;
                        case 'logged_in':
                            if(!Common::userId()) return false;
                            break;
                    }
                }
            }
            return true;
        }

        private function rules_validation($rule, $input)
        {
            if(strpos($rule, ':') !== false)
            {
                $rules = explode(':', $rule);
                if($rules[0] == 'int') {
                    if(is_numeric($input) === false)
                    {
                        return false;
                    }
                }
                if($rules[0] == 'string') {
                    if(is_array($input))
                    {
                        return false;
                    }
                }
                if($rules[0] == 'array') {
                    if(!is_array($input))
                    {
                        return false;
                    }
                }
                $rule = $rules[1];
            }

            if(!empty($rule)) {
                if (strpos($rule, ','))
                {
                    $rules = explode(',', $rule);
                    foreach ($rules as $rule)
                    {
                        $rule = trim($rule);
                        if (!\Morphine\Base\Engine\Security\Rules::$rule($input)) return false;
                    }
                }
                else
                {
                    return \Morphine\Base\Engine\Security\Rules::$rule($input);
                }
            }
            else return true;
        }

        private function exception($e)
        {
            switch ($e)
            {
                case 'E_RULES_FAILED':
                case 'E_REQUIRED_PARAM_NOT_FOUND':
                case 'E_ACCESS_CONTROL_FAILURE':
                    $this->custom_display = $this->surface->exception[$e];
                    parent::$req_data['exception'] = $e;
                    break;
            }
        }

        private function param_exists($accepted_methods, $required_param)
        {
            foreach ($accepted_methods as $method)
            {
                if ('trail' === $method)
                {
                    if (isset($this->trail->$required_param))
                    {
                        return $this->trail->$required_param;
                    }
                }
                elseif(isset(self::$data[strtolower($method)][$required_param]))
                {
                    return parent::$data[strtolower($method)][$required_param];
                }
            }
            return false;
        }

        public function check_redirection(string $target_display):void
        {
            if(!empty($target_display))
            {
                if(strpos($target_display, '->'))
                {
                    $target_display_parts = explode('->', $target_display);
                    if(strtolower($target_display_parts[0]) == 'r')
                    {
                        Route::redirect($target_display_parts[1]);
                    }
                }
            }
        }

        public function Flag($exception)
        {
            switch ($exception)
            {
                /**
                 * STRICT_BOUNDARIES :
                 * ==================
                 * > Any request that originated from another domain than the current HOST header
                 * > This can be spoofed, anyway, this is used only as a helper during dispatch .
                 * > TIP: Never take referer seriously, as it can be changed to whatever possible.
                 */
                case 'STRICT_BOUNDARIES':
                    $this->flagged['STRICT_BOUNDARIES'] = true;
                    break;

                /**
                 * DIRECT_ACCESS :
                 * ===============
                 * > Any request that came directly without clicking anything on the application
                 * > Example 1: link direct access from the browser's toolbar
                 * > Example 2: usage of automated tools (Crawlers/Spiders, Fuzzers ..etc)
                 * > Example 3: Visits from Bookmarks / browser's History
                 * TIP: This can be spoofed as it's dependent on the 'referer' HTTP header supplied;
                 *      Otherwise it can help Dispatcher in it's process - but not centralized -
                 * TIP2: This may enforce more layers of security checks, and support security plugins.
                 * TIP3: Never get satisfied with this data, as it only can be used as a helper.
                 */
                case 'DIRECT_ACCESS':
                    $this->flagged['DIRECT_ACCESS'] = true;
                    break;

                /**
                 * BAD_REFERER:
                 * ===========
                 * > If a user-crafted referer is not a valid URL
                 */
                case 'BAD_REFERER':
                    $this->flagged['BAD_REFERER'] = true;
                    break;

                /**
                 * LOGGED_OUT:
                 * ===========
                 * > If a visitor is not logged-in
                 */
                case 'LOGGED_OUT':
                    $this->flagged['LOGGED_OUT'] = true;
                    break;

                /**
                 * UNKNOWN_EVENT:
                 * ===========
                 * > If a visitor is not logged-in
                 */
                case 'UNKNOWN_EVENT':
                    $this->flagged['UNKNOWN_EVENT'] = true;
                    break;

            }
        }

        private function register_channel(&$channels_hook=false, $callback = false)
        {
            /**
             * Reserve this to Plugins (First step on every plugin).
             */
        }
    }
}