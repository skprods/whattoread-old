<?php

namespace App\Telegram\Commands;

class AddBookCommand extends TelegramCommand
{
    protected $name = 'addbook';

    protected $description = 'Добавить прочитанную книгу';

    public function handleCommand()
    {
        $this->replyWithMessage(['text' => "Давайте добавим новую книгу, которую вы прочитали. Для начала, напишите её название"]);
    }
}
