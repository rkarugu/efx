<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Salesman Web Login Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration determines whether salesmen are allowed to log into
    | the web interface. By default, salesmen are restricted to mobile app
    | access only. Set this to true to enable web access for salesmen.
    |
    */
    'allow_web_login' => env('ALLOW_SALESMAN_WEB_LOGIN', true),
    'allow_mobile_api' => env('ALLOW_SALESMAN_MOBILE_API', true),

    /*
    |--------------------------------------------------------------------------
    | Salesman Role Detection
    |--------------------------------------------------------------------------
    |
    | These settings help identify which users should be treated as salesmen
    | for the purpose of web login permissions.
    |
    */
    'sales_role_ids' => [169, 170], // Known sales role IDs
    'sales_role_keywords' => ['sales', 'salesman', 'representative'], // Keywords in role names
    
    /*
    |--------------------------------------------------------------------------
    | Salesman Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the salesman web dashboard and order taking interface.
    |
    */
    'dashboard' => [
        'orders_per_page' => 25,
        'customers_per_page' => 25,
        'session_timeout' => 15, // minutes
    ],
];
