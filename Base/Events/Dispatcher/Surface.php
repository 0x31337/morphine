<?php
/**
 * Surface class;
 *  $name: Name of the Surface
 *  $parameters : Expected parameters of the Surface
 *  $required_parameters : Required expected parameters of the Surface
 *  $optional_parameters : Optional expected parameters of the Surface
 *  $accepted_methods : Accepted HTTP methods array
 *  $operation : Operation::class method callback
 *  $display : Requested view name, will be rendered after the operation is executed (if any)
 *  $exception : An array contains exception codes as keys and display name as values, to be rendered
 *               after the exception is flagged.
 *  $access_control : An array of roles permitted to pass from Dispatcher through this Surface;
 *          We have :
 *                  * admin       : Only the Admin can pass through
 *                  * logged_in   : Only who is logged_in can pass through
 *                  * public      : Everyone can pass through
 *                  * CUSTOM      : we can define a custom check in Dispatcher::class 'access_control_validation'
 */

namespace Morphine\Base\Events\Dispatcher;

use Morphine\Base\Events\Events;

if( !class_exists(Surface::class))
{
    class Surface
    {
        public array $flagged;
        public string $name;
        public array $parameters;
        public array $required_parameters;
        public array $optional_parameters;
        public array $accepted_methods;
        public string $operation;
        public string $display;
        public array $exception;
        public array $access_control;

        public function __construct($current_channel, $event)
        {
            $this->flagged = [];
            $event = ($event=='')?'visit':$event;
            $ch = $current_channel[$event]??Channels::get('418')['visit'];

            $this->name = $event;
            foreach ( $ch as $key => $value)
            {
                $this->set($key, $value);
            }
            $this->split_parameters();
        }

        public function set($property, $data){
            $this->$property = $data;
        }

        private function split_parameters()
        {
            if(isset($this->parameters['required']))
            {
                $this->required_parameters = $this->parameters['required'];
            }
            if(isset($this->parameters['optional']))
            {
                $this->optional_parameters = $this->parameters['optional'];
            }
        }
    }
}