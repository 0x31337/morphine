<?php


namespace Morphine\Base\Renders;

if (!class_exists(InputViewController::class))
{
    class InputViewController
    {
        public static function text($name, $placeholder, $class, $id, $default_value)
        {
            return "<input type='text' 
                            autocomplete='off'
                            name='$name'
                            placeholder='$placeholder' 
                            class='$class' 
                            id='$id', 
                            value='$default_value'>";
        }

        public static function file($name, $class, $id)
        {
            return "<input type='file' 
                            name='$name'
                            class='$class' 
                            id='$id'>";
        }

        public static function password($name, $placeholder, $class, $id, $default_value)
        {
            return "<input type='password' 
                            name='$name'
                            placeholder='$placeholder' 
                            class='$class' 
                            id='$id', 
                            value='$default_value'>";
        }

        public static function date($name, $class, $id, $default_value)
        {
            return "<input type='date' 
                            name='$name'
                            class='$class' 
                            id='$id', 
                            value='$default_value'>";
        }

        public static function radio(...$args)
        {
            $radio = '';
            foreach($args as $radio_btn)
            {
                $checked = ($radio_btn['checked_val'] == $radio_btn['value'])?'checked':'';
                $radio .= '<label>'.$radio_btn['label'].'<input type="radio" name="'.$radio_btn['name'].'" 
                            id="'.$radio_btn['id'].'" value="'.$radio_btn['value'].'" 
                            '.$checked.'></label>';
            }
            return $radio;
        }

        public static function textarea($name, $placeholder, $class, $id, $default_value)
        {
            return "<textarea name='$name' id='$id' class='$class' placeholder='$placeholder'>$default_value</textarea>";
        }
        public static function select($name, $type, $class, $id, $default_value)
        {
            // You can add your custom select list
            $options = '';
            if ($type == "countries") {
                $countries = json_decode(file_get_contents(AppGlobals::$AssetsDir . 'countries.json'));

                foreach ($countries as $country) {
                    if($country->code == $default_value)
                    {
                        $options .= "<option value='".$country->code."' selected>".$country->name."</option>";
                    }
                    else
                    {
                        $options .= "<option value='".$country->code."'>".$country->name."</option>";
                    }
                }
            }

            if(is_array($type))
            {
                foreach ($type as $key => $value)
                {
                    if($key==$default_value)
                    {
                        $options .= "<option value='$key' selected>$value</option>";
                    }
                    else
                    {
                        $options .= "<option value='$key'>$value</option>";
                    }
                }
            }

            return "<select
                               name='$name'
                               class='$class'
                               id='$id'>
                               
                               ".$options.
                '</select>';

        }

        public static function submit($value, $id, $class)
        {
            return "<input type='submit' class='$class' id='$id' value='$value'>";
        }

        public static function hidden($name, $value)
        {
            return "<input type='hidden' value='$value' name='$name' />";
        }
    }
}