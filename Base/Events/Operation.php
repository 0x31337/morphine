<?php

/**
 * Static callbacks
 */
namespace Morphine\Base\Events;


use Morphine\Base\Renders\Render;
use Morphine\Base\Renders\View;

class Operation extends Events
{
    // Uses the AJAX View's ConditionalViews to render a specific response based on $req_data
    public static function ajax_conditional_view(array $req_data)
    {
        (new Render(
          'ajax',
          new Pages(),
            $req_data
          )
        );
        exit;
    }

    // Renders a whole View and return it in the response
    public static function dynamic_view(array $req_data)
    {
        (new Render(
            $req_data['view_name'],
            new Pages(),
            $req_data
        )
        );
        exit;
    }

    // ajp_ prefix stands for an Ajax Pure, which only executes Operations without needed response
    public static function ajp_s1_action(array $req_data){
            switch ($req_data['action'])
            {
                case 'exampleAction':
                    # Call an Operation
                    break;
            }

        exit;
    }

    // You can use Models to return an array, and turn it to a JSON HTTP response
    public static function json_response(array $req_data){
        $response = json_encode(array());
        switch ($req_data['request'])
        {
            case 'exampleRequestParam':
                $array = [];
                echo json_encode($array, true);
                break;
        }
        exit;
    }

    // You can use models to return any response it's going to be the HTTP response
    public static function raw_response(array $req_data)
    {
        switch (true) {
            case (strpos($req_data['request'], 'exampleRequest') !== false):
                $raw_response = '1';
                echo $raw_response;
                break;
        }
        exit;
    }
    // and so on ...
}
