<?php
/**
 * The Class where to declare new surfaces
 */
namespace Morphine\Base\Events\Dispatcher;

if (!class_exists(Channels::class))
{

    class Channels
    {
        public static array $channels;

        /**
         * This is where you need to add your new Surface
         */
        public static function init()
        {
            self::$channels = array(

                /*
                * Home Channel
                */
                'home' =>
                    [
                        /*
                         * VISIT Surface
                         */
                        'visit' =>
                            [
                                'accepted_methods' => ['trail'],
                                'access_control' => ['guest'],
                                'parameters' => [],
                                'display' => 'guestframe'
                            ]
                    ],

                /*
                * HTTP 404 Not Found Channel
                */
                '404' =>
                    [
                        /*
                         * VISIT Surface
                         */
                        'visit' =>
                            [
                                'accepted_methods' => ['GET', 'POST'],
                                'access_control' => ['guset'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login'
                                    ],
                                'parameters' => [],
                                'display' => 'notfound'
                            ]
                    ],

                /*
                * Operations Only Ajax Channel (No view response, check next channel for ajax views)
                */
                'ajax_pure' =>
                    [
                        /*
                         * VISIT Surface
                         */
                        'visit' =>
                            [
                                'accepted_methods' => ['GET', 'POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login'
                                    ],
                                'parameters' => [],
                                'display' => 'nothing'
                            ],

                        /*
                         * AJP Sample Surface
                         */
                        'surface_1_example' =>
                            [
                                'accepted_methods' => ['POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login',
                                        'E_REQUIRED_PARAM_NOT_FOUND' => 'exception',
                                        'E_RULES_FAILED' => 'exception',
                                    ],
                                'parameters' => [
                                    'required' =>
                                        [
                                            'string:' => ['action']
                                        ],
                                    'optional' =>
                                        [

                                        ]
                                ],
                                'operation' => 'ajp_s1_action'
                            ]
                    ],

                /*
                * Ajax Channel
                */
                'ajax' =>
                    [
                        /*
                         * VISIT Surface
                         */
                        'visit' =>
                            [
                                'accepted_methods' => ['GET'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login'
                                    ],
                                'parameters' => [],
                                'display' => 'nothing'
                            ],

                        /*
                         * Ajax Sample Surface
                         */
                        'json' =>
                            [
                                'accepted_methods' => ['POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login',
                                        'E_REQUIRED_PARAM_NOT_FOUND' => 'nothing',
                                    ],
                                'parameters' => [
                                    'required' =>
                                        [
                                            'string:' => ['request']
                                        ]
                                ],
                                'operation' => 'json_response'
                            ],

                        /*
                         * Ajax Request To Extract Specific RAW Data From the Database To Print or Deal With It From JS
                         */
                        'raw' =>
                            [
                                'accepted_methods' => ['POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login',
                                        'E_REQUIRED_PARAM_NOT_FOUND' => 'nothing',
                                    ],
                                'parameters' => [
                                    'required' =>
                                        [
                                            'string:' => ['request', 'beacon_sig']
                                        ]
                                ],
                                'operation' => 'raw_response'
                            ],

                        /*
                         * Ajax Request to Load A Dynamic View
                         */
                        'view' =>
                            [
                                'accepted_methods' => ['POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login'
                                    ],
                                'parameters' => [
                                    'required' =>
                                        [
                                            'string:' => ['view_name']
                                        ],
                                    'optional' =>
                                        [
                                        'string:' => ['param1', 'param2']
                                        ]
                                ],
                                'operation' => 'dynamic_view'
                            ],

                        /*
                         * Ajax Request to Load Beacons List View
                         */
                        'ajax_cond_view' =>
                            [
                                'accepted_methods' => ['POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login'
                                    ],
                                'parameters' => [
                                    'required' =>
                                        [
                                            'string:' => ['status']
                                        ]
                                ],
                                'operation' => 'ajax_conditional_view'
                            ],
                    ],

                /*
                * HTTP 418 Unknown Event Channel
                */
                '418' =>
                    [
                        /*
                         * VISIT Surface
                         */
                        'visit' =>
                            [
                                'accepted_methods' => ['POST'],
                                'access_control' => ['logged_in'],
                                'exception' =>
                                    [
                                        'E_ACCESS_CONTROL_FAILURE' => 'R->login'
                                    ],
                                'parameters' => [],
                                'display' => 'unknownevent'
                            ]
                    ],
            );
        }

        public static function exists($channel_name)
        {
            return in_array($channel_name, array_keys(self::$channels));
        }

        public static function get($channel_name)
        {
            return self::$channels[$channel_name];
        }
    }
}
