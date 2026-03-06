<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The default registry connection to use. This connection will be used
    | when no specific connection name is provided to the Dockhand facade.
    |
    */

    'default' => env('DOCKHAND_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Registry Connections
    |--------------------------------------------------------------------------
    |
    | Configure one or more registry connections. Each connection is fully
    | self-contained with its own driver, base URI, auth config, and logging.
    |
    | Supported drivers: "distribution", "zot"
    | Supported auth drivers: "jwt", "basic", "bearer", "apikey", "null"
    |
    */

    'connections' => [
        'default' => [
            'driver' => env('DOCKHAND_DRIVER', 'distribution'),
            'base_uri' => env('DOCKHAND_BASE_URI', 'http://localhost:5000/v2/'),
            'logging' => [
                'driver' => env('DOCKHAND_LOG_DRIVER', 'stack'),
            ],
            'auth' => [
                'driver' => env('DOCKHAND_AUTH_DRIVER', 'jwt'),
                'jwt_private_key' => env('DOCKHAND_PRIVATE_KEY'),
                'jwt_public_key' => env('DOCKHAND_PUBLIC_KEY'),
                'authority_name' => env('DOCKHAND_AUTHORITY_NAME', 'auth'),
                'registry_name' => env('DOCKHAND_REGISTRY_NAME', 'registry'),
            ],
        ],

        // Example: second registry using Zot with basic auth
        // 'zot-staging' => [
        //     'driver' => 'zot',
        //     'base_uri' => env('ZOT_STAGING_BASE_URI', 'http://localhost:5050/v2/'),
        //     'logging' => [
        //         'driver' => env('ZOT_STAGING_LOG_DRIVER', 'stack'),
        //     ],
        //     'auth' => [
        //         'driver' => 'basic',
        //         'username' => env('ZOT_STAGING_USERNAME'),
        //         'password' => env('ZOT_STAGING_PASSWORD'),
        //     ],
        // ],

        // Example: third registry using Zot with API key
        // 'zot-prod' => [
        //     'driver' => 'zot',
        //     'base_uri' => env('ZOT_PROD_BASE_URI'),
        //     'logging' => [
        //         'driver' => env('ZOT_PROD_LOG_DRIVER', 'stack'),
        //     ],
        //     'auth' => [
        //         'driver' => 'apikey',
        //         'api_key' => env('ZOT_PROD_API_KEY'),
        //     ],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications settings
    |--------------------------------------------------------------------------
    |
    | This route specifies the endpoint for receiving notifications. This
    | should match the notifications.endpoint.url parameter in the
    | registry configuration. Dockhand will receive them and
    | trigger the appropriate events in the application.
    |
    */

    'notifications' => [
        'enabled' => env('DOCKHAND_NOTIFICATIONS_ENABLED', true),
        'route' => env('DOCKHAND_NOTIFICATIONS_ROUTE', '/dockhand/notify'),
    ],
];
