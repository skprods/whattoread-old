<?php

namespace App\Telegram\Commands;

class BookRecsCommand extends TelegramCommand
{
    protected $name = 'bookrecs';

    protected $description = 'Получить рекомендации на книгу';

    public function handleCommand()
    {
        $this->replyWithMessage(['text' => "Для какой книге нужно найти похожие? Напишите название и/или автора."]);
    }
}
