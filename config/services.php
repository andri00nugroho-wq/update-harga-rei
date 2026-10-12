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

        // Dipertahankan untuk kompatibilitas konfigurasi lama.
        'api_key' => env('GOOGLE_API_KEY'),

        // Kredensial Service Account untuk spreadsheet privat.
        'credentials' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),

        // Spreadsheet utama: Jawa / Nasional.
        'spreadsheet_id' => env('GOOGLE_SHEETS_ID'),

        // Spreadsheet regional: Kalimantan dan Sumatera.
        // Jika tidak diisi, gunakan ID spreadsheet utama.
        'region_spreadsheet_id' => env(
            'GOOGLE_SHEETS_REGION_ID',
            env('GOOGLE_SHEETS_ID')
        ),

        // Spreadsheet khusus Logam Mulia.
        'logam_mulia_spreadsheet_id' => env(
            'GOOGLE_SHEETS_LOGAM_MULIA_ID'
        ),

        // GID tab default.
        'sheet_gid' => env(
            'GOOGLE_SHEETS_GID',
            '0'
        ),

        // Nama tab default jika dibutuhkan.
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
