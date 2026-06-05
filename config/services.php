<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'socialite' => [
        'wechat' => [
            'client_id' => env('WEIXIN_KEY'),
            'client_secret' => env('WEIXIN_SECRET'),
            'redirect' => env('WEIXIN_REDIRECT_URI'),
        ],
    ],

    'openclaw' => [
        'gateway_url' => env('OPENCLAW_GATEWAY_URL'),
    ],

    'local_codex' => [
        'bridge_url' => env('LOCAL_CODEX_BRIDGE_URL'),
        'bridge_token' => env('LOCAL_CODEX_BRIDGE_TOKEN'),
        'model' => env('LOCAL_CODEX_MODEL', 'local-codex/codex-cli'),
    ],

    'local_gemini' => [
        'bridge_url' => env('LOCAL_GEMINI_BRIDGE_URL'),
        'bridge_token' => env('LOCAL_GEMINI_BRIDGE_TOKEN'),
        'model' => env('LOCAL_GEMINI_MODEL', 'local-gemini/gemini-cli'),
    ],

    'local_claude' => [
        'bridge_url' => env('LOCAL_CLAUDE_BRIDGE_URL'),
        'bridge_token' => env('LOCAL_CLAUDE_BRIDGE_TOKEN'),
        'model' => env('LOCAL_CLAUDE_MODEL', 'local-claude/claude-cli'),
    ],

    'client_ip_override' => [
        'source' => env('CLIENT_IP_OVERRIDE_SOURCE'),
        'target' => env('CLIENT_IP_OVERRIDE_TARGET'),
    ],

];
