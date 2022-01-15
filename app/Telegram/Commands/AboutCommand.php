<?php

namespace App\Telegram\Commands;

use App\Models\Book;
use App\Models\BookAssociation;
use App\Models\Genre;
use Illuminate\Support\Facades\DB;

class AboutCommand extends TelegramCommand
{
    protected $name = 'about';

    protected $description = 'Подробная информация о боте';

    public function handleCommand()
    {
        $booksCount = Book::query()->where('status', Book::ACTIVE_STATUS)->count();
        $booksMessage = $this->getBooksMessage($booksCount);

        $authorsCount = DB::table('books')
            ->selectRaw("count(distinct author) as authorsCount")
            ->where('status', Book::ACTIVE_STATUS)
            ->first()
            ->authorsCount;
        $authorsMessage = $this->getAuthorMessage($authorsCount);

        $genresCount = Genre::query()->where('status', Genre::ACTIVE_STATUS)->count();
        $genresMessage = $this->getGenresMessage($genresCount);

        $associationsCount = BookAssociation::query()->count();
        $associationsMessage = $this->getAssociationsMessage($associationsCount);

        $text = "WhatToRead - бот, который подбирает книги на основе ваших предпочтений.\n\n";

        $text .= "Текущая версия: " . config('app.version') . "\n\n";

        $text .= "Сейчас наш бот - это:\n";
        $text .= "- $booksMessage от $authorsMessage;\n";
        $text .= "- $genresMessage;\n";
        $text .= "- $associationsMessage;\n";
        $text .= "\n";

        $text .= "*Система рекомендаций*\n\n";

        $text .= "В основе бота лежит экспериментальная рекомендательная система. Она подбирает книги не только по ";
        $text .= "жанрам и автору, но и анализирует описание и содержание книги. Помимо этого рекомендации строятся ";
        $text .= "на основе ассоциаций.\n\n";

        $text .= "Например, когда вы добавляете прочитанную книгу, мы просим вас оценить её и указать ассоциации, ";
        $text .= "вызванные этой книгой. В зависимости от ваших ответов мы определяем жанры и особенности книг, ";
        $text .= "которые вас цепляют. Такую же информацию указывают другие пользователи, благодаря чему мы ";
        $text .= "составляем ассоциативный словарь для каждой книги. Собрав эту информацию, мы можем определить, ";
        $text .= "какие книги вам могут понравиться.\n\n";

        $text .= "В данный момент система находится в стадии разработки, поэтому некоторые подборки могут ";
        $text .= "выглядеть некорректно. Однако мы непрерывно учим систему давать более точные рекомендации. ";
        $text .= "Если подборка выдаёт совсем не то, что хотелось бы, попробуйте чуть позже. Возможно, системе ";
        $text .= "удастся вас удивить.\n\n";

        $text .= "*Конфиденциальность*\n\n";
        $text .= "Мы не храним никакую информацию о вас за исключением вашего идентификатора и имени в Telegram.";

        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'markdown',
        ]);
    }

    private static function getBooksMessage(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count книга";
            case 2:
            case 3:
            case 4:
                return "$count книги";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count книг";
            default:
                return "";
        }
    }

    private function getAuthorMessage(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count автор";
            case 2:
            case 3:
            case 4:
                return "$count автора";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count авторов";
            default:
                return "";
        }
    }

    private function getGenresMessage(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count жанр";
            case 2:
            case 3:
            case 4:
                return "$count жанра";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count жанров";
            default:
                return "";
        }
    }

    private function getAssociationsMessage(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count книжная ассоциация";
            case 2:
            case 3:
            case 4:
                return "$count книжные ассоциации";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count книжных ассоциаций";
            default:
                return "";
        }
    }
}
