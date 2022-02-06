<?php

return [
    'bots' => [
        'whattoread' => [
            'token'         => env('TELEGRAM_WHATTOREAD_BOT_TOKEN'),
            'webhook_url'   => env('TELEGRAM_WHATTOREAD_WEBHOOK_URL'),
            'allow_dialog'  => true,
            'commands'      => [
                \App\Telegram\Commands\StartCommand::class,
                \App\Telegram\Commands\HelpCommand::class,
                \App\Telegram\Commands\AboutCommand::class,
                \App\Telegram\Commands\MyBooksCommand::class,
                \App\Telegram\Commands\BookCommand::class,
            ],
            'dialogs'       => [
                \App\Telegram\Dialogs\AddBookDialog::class,
                \App\Telegram\Dialogs\BookRecsDialog::class,
                \App\Telegram\Dialogs\RecsDialog::class,
            ],
            'allowed_chats' => [
                env('ERROR_CHAT_ID'),
            ],
            'allowed_users' => [],
            'freeHandler' => \App\Telegram\CommonHandler::class,
        ],
    ],

    /** Default bot */
    'default' => 'whattoread',

    /**
     * Http-клиент для отправки запросов в Telegram.
     *
     * Если встроенного функционала недостаточного, можно указать
     * кастомный обработчик, который должен реализовывать интерфейс
     * @see SKprods\Telegram\Clients\SkprodsHttpClient
     */
    'httpClient' => SKprods\Telegram\Clients\SkprodsHttpClient::class,

    /**
     * Конфигурация ChatInfo
     *
     * С помощью этой конфигурации можно настроить, где будет храниться
     * информация о чатах, такая как текущая и предыдущая команда, а также
     * информация о диалоге.
     *
     * По умолчанию поддерживаются три типа хранения: file, redis, custom.
     *
     * - file - хранение данных о чате в JSON-файле. Сам файл будет находиться
     * в storage/app/chatInfo.json;
     *
     * - redis - хранение данных о чате в Redis. В таком случае нужно обязательно
     * указать ключ connection, в котором указать название соединение. Найти его
     * можно в config/database.php в секции 'redis';
     *
     * - custom - кастомный обработчик хранения. Определите класс-обработчик в
     * ключе 'handler'. Он должен реализовывать этот интерфейс:
     * @see SKprods\Telegram\Writers\Writer
     */
    'chatInfo' => [
        'driver' => 'redis',
        'connection' => 'default',
        'handler' => null,
    ],
];