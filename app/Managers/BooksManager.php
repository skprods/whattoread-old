<?php

namespace App\Managers;

use App\Books\Samolit;
use App\Parsers\SamolitParser;
use Illuminate\Support\Facades\DB;

class BooksManager
{
    private BookManager $bookManager;
    private GenreManager $genreManager;

    public function __construct()
    {
        $this->bookManager = app(BookManager::class);
        $this->genreManager = app(GenreManager::class);
    }

    public function addFromSamolit(string $content): bool
    {
        /** @var Samolit $parsedBook */
        $parsedBook = app(SamolitParser::class)->getBook($content);

        try {
            DB::beginTransaction();

            /** Создаём или обновляем жанры */
            $genres = $this->genreManager->bulkCreateOrUpdate($parsedBook->genres);

            /** Создаем книгу и добавляем к ней жанры */
            $this->bookManager->createOrUpdate($parsedBook->toArray());
            $this->bookManager->addGenres($genres->pluck('id')->toArray());

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        return true;
    }
}
