<?php
// Application channels and surfaces configuration
return [
    'home' => [
        'visit' => [
            'accepted_methods' => ['trail'],
            'access_control' => ['guest'],
            'parameters' => [],
            'display' => 'guestframe'
        ]
    ],
    '404' => [
        'visit' => [
            'accepted_methods' => ['GET', 'POST'],
            'access_control' => ['guest'],
            'parameters' => [],
            'display' => 'notfound'
        ]
    ],
    'ajax_pure' => [
        'visit' => [
            'accepted_methods' => ['GET', 'POST'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login'
            ],
            'parameters' => [],
            'display' => 'nothing'
        ],
        'surface_1_example' => [
            'accepted_methods' => ['POST'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login',
                'E_REQUIRED_PARAM_NOT_FOUND' => 'exception',
                'E_RULES_FAILED' => 'exception',
            ],
            'parameters' => [
                'required' => [
                    'string:' => ['action']
                ],
                'optional' => []
            ],
            'operation' => 'ajpS1Action'
        ]
    ],
    'ajax' => [
        'visit' => [
            'accepted_methods' => ['GET'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login'
            ],
            'parameters' => [],
            'display' => 'nothing'
        ],
        'json' => [
            'accepted_methods' => ['POST'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login',
                'E_REQUIRED_PARAM_NOT_FOUND' => 'nothing',
            ],
            'parameters' => [
                'required' => [
                    'string:' => ['request']
                ]
            ],
            'operation' => 'jsonResponse'
        ],
        'raw' => [
            'accepted_methods' => ['POST'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login',
                'E_REQUIRED_PARAM_NOT_FOUND' => 'nothing',
            ],
            'parameters' => [
                'required' => [
                    'string:' => ['request', 'beacon_sig']
                ]
            ],
            'operation' => 'rawResponse'
        ],
        'view' => [
            'accepted_methods' => ['POST'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login'
            ],
            'parameters' => [
                'required' => [
                    'string:' => ['view_name']
                ],
                'optional' => [
                    'string:' => ['param1', 'param2']
                ]
            ],
            'operation' => 'dynamicView'
        ],
        'ajax_cond_view' => [
            'accepted_methods' => ['POST'],
            'access_control' => ['logged_in'],
            'exception' => [
                'E_ACCESS_CONTROL_FAILURE' => 'R->login'
            ],
            'parameters' => [
                'required' => [
                    'string:' => ['status']
                ]
            ],
            'operation' => 'ajaxConditionalView'
        ]
    ]
]; 