<?php

namespace App\Telegram\Commands;

use App\Models\Book;
use App\Models\BookAssociation;
use App\Models\Genre;
use App\Telegram\TelegramCommand;
use App\Traits\HasDeclination;
use Illuminate\Support\Facades\DB;

class AboutCommand extends TelegramCommand
{
    use HasDeclination;

    public string $name = 'about';
    public string $description = 'Подробная информация о боте';

    public function handle()
    {
        $booksCount = Book::query()->where('status', Book::ACTIVE_STATUS)->count();
        $booksMessage = $this->getBooksDeclination($booksCount);

        $authorsCount = DB::table('books')
            ->selectRaw("count(distinct author) as authorsCount")
            ->where('status', Book::ACTIVE_STATUS)
            ->first()
            ->authorsCount;
        $authorsMessage = $this->getAuthorDeclination($authorsCount);

        $genresCount = Genre::query()->where('status', Genre::ACTIVE_STATUS)->count();
        $genresMessage = $this->getGenresDeclination($genresCount);

        $associationsCount = BookAssociation::query()->count();
        $associationsMessage = $this->getAssociationsDeclination($associationsCount);

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
}
