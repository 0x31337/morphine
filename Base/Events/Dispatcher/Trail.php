<?php
/**
 * This is the Trail class;
 * It consists of two things :
 * 1- target
 * 2- Data passed by GET
 */

namespace Morphine\Base\Events\Dispatcher;

class Trail
{
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }
}