<?php

return [
    'bots' => [

        'whattoread' => [
            'username'            => 'WhatToRead',
            'token'               => env('TELEGRAM_WHATTOREAD_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            'certificate_path'    => env('TELEGRAM_WHATTOREAD_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'webhook_url'         => env('TELEGRAM_WHATTOREAD_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            'commands'            => [
                \App\Http\Commands\WhatToReadBot\StartCommand::class,
                \App\Http\Commands\WhatToReadBot\HelpCommand::class,
            ],
        ],

        'associativeBooks' => [
            'username'            => 'WhatToRead',
            'token'               => env('TELEGRAM_WHATTOREAD_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            'certificate_path'    => env('TELEGRAM_WHATTOREAD_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'webhook_url'         => env('TELEGRAM_WHATTOREAD_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            'commands'            => [
                \App\Http\Commands\WhatToReadBot\StartCommand::class,
                \App\Http\Commands\WhatToReadBot\HelpCommand::class,
            ],
        ],

    ],

    'default' => 'whattoread',

    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    'http_client_handler' => null,

    'resolve_command_dependencies' => true,

    'commands' => [
        Telegram\Bot\Commands\HelpCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Groups [Optional]
    |--------------------------------------------------------------------------
    |
    | You can organize a set of commands into groups which can later,
    | be re-used across all your bots.
    |
    | You can create 4 types of groups:
    | 1. Group using full path to command classes.
    | 2. Group using shared commands: Provide the key name of the shared command
    | and the system will automatically resolve to the appropriate command.
    | 3. Group using other groups of commands: You can create a group which uses other
    | groups of commands to bundle them into one group.
    | 4. You can create a group with a combination of 1, 2 and 3 all together in one group.
    |
    | Examples shown below are by the group type for you to understand each of them.
    */
    'command_groups'               => [
        /* // Group Type: 1
           'commmon' => [
                Acme\Project\Commands\TodoCommand::class,
                Acme\Project\Commands\TaskCommand::class,
           ],
        */

        /* // Group Type: 2
           'subscription' => [
                'start', // Shared Command Name.
                'stop', // Shared Command Name.
           ],
        */

        /* // Group Type: 3
            'auth' => [
                Acme\Project\Commands\LoginCommand::class,
                Acme\Project\Commands\SomeCommand::class,
            ],

            'stats' => [
                Acme\Project\Commands\UserStatsCommand::class,
                Acme\Project\Commands\SubscriberStatsCommand::class,
                Acme\Project\Commands\ReportsCommand::class,
            ],

            'admin' => [
                'auth', // Command Group Name.
                'stats' // Command Group Name.
            ],
        */

        /* // Group Type: 4
           'myBot' => [
                'admin', // Command Group Name.
                'subscription', // Command Group Name.
                'status', // Shared Command Name.
                'Acme\Project\Commands\BotCommand' // Full Path to Command Class.
           ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Shared commands let you register commands that can be shared between,
    | one or more bots across the project.
    |
    | This will help you prevent from having to register same set of commands,
    | for each bot over and over again and make it easier to maintain them.
    |
    | Shared commands are not active by default, You need to use the key name to register them,
    | individually in a group of commands or in bot commands.
    | Think of this as a central storage, to register, reuse and maintain them across all bots.
    |
    */
    'shared_commands'              => [
        // 'start' => Acme\Project\Commands\StartCommand::class,
        // 'stop' => Acme\Project\Commands\StopCommand::class,
        // 'status' => Acme\Project\Commands\StatusCommand::class,
    ],
];
