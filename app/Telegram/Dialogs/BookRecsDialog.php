<?php

namespace App\Telegram\Dialogs;

use App\Services\BooksService;

class BookRecsDialog extends Dialog
{
    private string $name = 'bookrecs';

    private BooksService $booksService;

    protected array $steps = [
        'name', // название и автор книги
    ];

    public function nameStep(string $message)
    {
        $this->booksService = app(BooksService::class);
        $books = $this->booksService->findBookInElastic($message, 15);

        if (!empty($books)) {
            $text = "Вот что мы нашли на наших книжных полках:\n\n";
            foreach ($books as $key => $item) {
                $sourceId = $item->_source['id'];
                $sourceAuthor = $item->_source['author'];
                $sourceTitle = $item->_source['title'];

                $wrKey = $key + 1;
                $text .= "#$wrKey: *{$sourceAuthor} - {$sourceTitle}*\n";
                $text .= "Рекомендации: /recs{$sourceId}\n\n";
                $this->chatInfo->dialog->search[$wrKey] = $sourceId;
            }

            $text .= "Если искомой книги нет в списке, не расстраивайтесь. ";
            $text .= "Вы можете добавить её с помощью команды /addbook и мы подберём для неё рекомендации.";
        } else {
            $text = "К сожалению, такой книги пока нет в нашей библиотеке. ";
            $text .= "Мы автоматически добавим её и скоро она появится в рекомендациях.";
        }

        $this->completeStep();
        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'markdown',
        ]);
    }
}
