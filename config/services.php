<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Configuration for third-party services used by the application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Postmark
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resend
    |--------------------------------------------------------------------------
    */

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Amazon SES
    |--------------------------------------------------------------------------
    */

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env(
            'AWS_DEFAULT_REGION',
            'us-east-1'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Slack
    |--------------------------------------------------------------------------
    */

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN'
            ),

            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Sheets
    |--------------------------------------------------------------------------
    */

    'google_sheets' => [
        'api_key' => env('GOOGLE_API_KEY'),

        'spreadsheet_id' => env(
            'GOOGLE_SHEETS_ID'
        ),

        'sheet_name' => env(
            'GOOGLE_SHEETS_SHEET_NAME'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Canva
    |--------------------------------------------------------------------------
    */

    'canva' => [
        'client_id' => env(
            'CANVA_CLIENT_ID'
        ),

        'client_secret' => env(
            'CANVA_CLIENT_SECRET'
        ),

        'redirect_uri' => env(
            'CANVA_REDIRECT_URI',
            'http://127.0.0.1:8000/canva/callback'
        ),

        'design_id' => env(
            'CANVA_DESIGN_ID'
        ),
    ],

];