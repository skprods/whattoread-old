<?php

namespace App\Telegram\Commands;

use App\Models\Book;
use App\Models\Genre;

class BookCommand extends TelegramCommand
{
    public bool $hasParam = true;

    protected $name = 'book{id}';

    protected $description = 'Подробная информация о книге';

    public function handleCommand()
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
        $text .= "{$book->author}\n\n";

        $genres = $book->genres->map(function (Genre $genre) {
            return "#" . str_replace(' ', '', ucwords_unicode($genre->name));
        })->toArray();

        if (count($genres)) {
            $text .= implode(' ', $genres) . "\n\n";
        }

        $text .= $book->description !== '' ? $book->description : "Описания пока нет";

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'markdown',
        ]);
    }
}
