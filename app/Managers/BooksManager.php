<?php

namespace App\Managers;

use App\Books\Samolit;
use App\Entities\ChatInfo;
use App\Models\Book;
use App\Models\TelegramUser;
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

    public function addFromTelegram(ChatInfo $chatInfo)
    {
        try {
            DB::beginTransaction();
            $dialog = $chatInfo->dialog;

            if ($dialog->selectedBookId) {
                $book = Book::find($dialog->selectedBookId);
                $this->bookManager->book = $book;
            } else {
                $book = $this->bookManager->firstOrCreate([
                    'title' => $dialog->messages['title'][0],
                    'author' => $dialog->messages['author'][0],
                ]);
            }

            if (isset($dialog->messages['genres'])) {
                $genres = collect();
                /** @var GenreManager $genreManager */
                $genreManager = app(GenreManager::class);

                foreach ($dialog->messages['genres'] as $genre) {
                    $genres->push(
                        $genreManager->firstOrCreate(['name' => $genre])
                    );
                }

                $this->bookManager->addGenres($genres->pluck('id')->toArray());
            }

            /** @var TelegramUser $telegramUser */
            $telegramUser = TelegramUser::getUserByTelegramId($chatInfo->id);
            app(TelegramUserBookManager::class)->createOrUpdate(['rating' => $dialog->bookRating], $telegramUser, $book);

            if (isset($dialog->messages['associations'])) {
                app(AssociationManager::class)->addForTelegramUser($dialog->messages['associations'], $book, $telegramUser);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
