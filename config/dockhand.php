<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base URI
    |--------------------------------------------------------------------------
    |
    | This is the base URI for the Docker registry API. It is used
    | to make HTTP(s) requests to the Docker registry's API.
    |
    */

    'base_uri' => env('DOCKHAND_BASE_URI', 'http://localhost:5000/v2/'),

    /*
    |--------------------------------------------------------------------------
    | JWT Private and Public Keys
    |--------------------------------------------------------------------------
    |
    | These are the paths to the private and public keys used for signing
    | and verifying JWT tokens. The keys should be in PEM format.
    |
    */

    'jwt_private_key' => env('DOCKHAND_PRIVATE_KEY'),
    'jwt_public_key' => env('DOCKHAND_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Authority and Registry Names
    |--------------------------------------------------------------------------
    |
    | These are the names of the authority and registry used in
    | the JWT tokens. You can put any name you want here.
    |
    */

    'authority_name' => env('DOCKHAND_AUTHORITY_NAME', 'auth'),
    'registry_name' => env('DOCKHAND_REGISTRY_NAME', 'registry'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | If you want to set a specific log driver for dockhand, this is the
    | place. If the driver is set to null, logging is disabled.
    |
    */

    'logging' => [
        'driver' => env('DOCKHAND_LOG_DRIVER', 'stack'),
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
