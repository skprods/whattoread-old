<?php

return [
    'bots' => [

        'whattoread' => [
            'username'            => 'WhatToRead',
            'token'               => env('TELEGRAM_WHATTOREAD_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            'certificate_path'    => env('TELEGRAM_WHATTOREAD_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'webhook_url'         => env('TELEGRAM_WHATTOREAD_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            'commands'            => [
                \App\Telegram\Commands\StartCommand::class,
                \App\Telegram\Commands\HelpCommand::class,
                \App\Telegram\Commands\AddBookCommand::class,
            ],
        ],

    ],

    'dialogs' => [
        'addbook' => \App\Telegram\Dialogs\AddBookDialog::class,
    ],

    'default' => 'whattoread',

    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    'http_client_handler' => null,

    'resolve_command_dependencies' => true,

    'commands' => [
        Telegram\Bot\Commands\HelpCommand::class,
    ],
];
