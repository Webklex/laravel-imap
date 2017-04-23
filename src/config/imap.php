<?php
/*
* File:     imap.php
* Category: config
* Author:   M. Goldenbaum
* Created:  24.09.16 22:36
* Updated:  -
*
* Description:
*  -
*/

return [

    /*
    |--------------------------------------------------------------------------
    | IMAP Host Address
    |--------------------------------------------------------------------------
    |
    | By default this the imap handler will use this given account.
    |
    */
    'default' => env('IMAP_DEFAULT_ACCOUNT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Available IMAP accounts
    |--------------------------------------------------------------------------
    |
    | Please list all IMAP accounts which you are planning to use within the
    | array below.
    |
    */
    'accounts' => [

        'default' => [
            'host'  => env('IMAP_HOST', 'localhost'),
            'port'  => env('IMAP_PORT', 993),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'), // Supported: false, 'ssl', 'tls'
            'validate_cert' => env('IMAP_VALIDATE_CERT', true),
            'username' => env('IMAP_USERNAME', 'root@example.com'),
            'password' => env('IMAP_PASSWORD', ''),
        ],

        /*
        'gmail' => [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'encryption' => 'ssl', // Supported: false, 'ssl', 'tls'
            'validate_cert' => true,
            'username' => 'example@gmail.com',
            'password' => 'PASSWORD',
        ],

        'another' => [
            'host' => '',
            'port' => 993,
            'encryption' => false, // Supported: false, 'ssl', 'tls'
            'validate_cert' => true,
            'username' => '',
            'password' => '',
        ]
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Available IMAP options
    |--------------------------------------------------------------------------
    |
    | Available php imap config parameters are listed below
    |   -Fetch option:
    |       FT_UID  => Message marked as read by fetching the message
    |       FT_PEEK => Fetch the message without setting the "read" flag
    |
    */
    'options' => [
        'fetch' => FT_UID,
    ]
];
