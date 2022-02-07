<?php

namespace App\Telegram\Commands;

use App\Models\Book;
use App\Models\Genre;
use App\Telegram\Dialogs\RecsDialog;
use App\Telegram\TelegramCommand;
use Illuminate\Support\Facades\Log;

class BookCommand extends TelegramCommand
{
    public bool $show = false;

    public string $name = 'book';
    public string $pattern = 'book{id}';
    public string $description = 'Подробная информация о книге';

    public function handle()
    {
        $bookId = $this->arguments['id'];
        /** @var Book $book */
        $book = Book::find($bookId);

        if (!$book) {
            $this->replyWithMessage([
                'text' => "Книга не найдена",
                'parse_mode' => 'markdown',
            ]);

            return;
        }

        $text = "*{$book->title}*\n";
        $text .= "*{$book->author}*\n\n";

        $genres = $book->genres->map(function (Genre $genre) {
            return "#" . str_replace(' ', '', ucwords_unicode($genre->name));
        })->toArray();

        if (count($genres)) {
            $text .= "Жанры: ";
            $text .= implode(' ', $genres) . "\n";
        }

        $text .= "Рекомендации: /" . RecsDialog::getCommandNameForBook($bookId);
        $text .= "\n\n";

        $text .= $book->description !== '' ? "*Описание*: \n" . $book->description : "Описания пока нет";

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'markdown',
        ]);
    }
}
