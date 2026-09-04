<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'discord' => [
        'client_id'             => env('DISCORD_CLIENT_ID'),
        'client_secret'         => env('DISCORD_CLIENT_SECRET'),
        'redirect'              => env('APP_URL') . '/auth/discord/callback',
        'webhook_mrs_racewell'  => env('DISCORD_WEBHOOK_MRS_RACEWELL'),
        'bot_token'             => env('DISCORD_BOT_TOKEN'),
        'guild_id'              => env('DISCORD_GUILD_ID'),
        'announcer_webhook'     => env('DISCORD_ANNOUNCER_WEBHOOK'),
        'xcl_member_role_id'    => env('DISCORD_XCL_MEMBER_ROLE_ID'),
        'rank_roles'            => [
            'alien'    => env('DISCORD_ROLE_RANK_ALIEN'),
            'platinum' => env('DISCORD_ROLE_RANK_PLATINUM'),
            'gold'     => env('DISCORD_ROLE_RANK_GOLD'),
            'silver'   => env('DISCORD_ROLE_RANK_SILVER'),
            'bronze'   => env('DISCORD_ROLE_RANK_BRONZE'),
            'rookie'   => env('DISCORD_ROLE_RANK_ROOKIE'),
        ],
    ],

    'steam' => [
        'api_key'       => env('STEAM_API_KEY'),
        'client_id'     => null,
        'client_secret' => env('STEAM_API_KEY'),
        'redirect'      => env('APP_URL') . '/auth/steam/callback',
    ],

    'openxbl' => [
        'api_key'              => env('XBOX_API_KEY'),
        'app_id'               => env('XBOX_APP_ID'),
        'app_secret'           => env('XBOX_APP_SECRET'),
        'azure_client_id'      => env('XBOX_AZURE_CLIENT_ID'),
        'azure_client_secret'  => env('XBOX_AZURE_VALUE'),
        'system_refresh_token' => env('XBOX_SYSTEM_REFRESH_TOKEN'),
        'redirect'             => env('APP_URL') . '/auth/xbox/callback',
    ],

    'psn_lookup' => [
        'api_key' => env('PSN_LOOKUP_API_KEY'),
    ],

];