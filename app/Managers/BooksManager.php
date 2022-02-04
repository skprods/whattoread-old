<?php

namespace App\Managers;

use App\Entities\SamolitBook;
use App\Events\BookDescriptionUpdated;
use App\Models\Book;
use App\Models\TelegramUser;
use App\Parsers\SamolitParser;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use SKprods\Telegram\Core\Telegram;

class BooksManager
{
    private BookManager $bookManager;
    private GenreManager $genreManager;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->bookManager = app(BookManager::class);
        $this->genreManager = app(GenreManager::class);

        $this->notificationService = app(NotificationService::class, ['telegram' => new Telegram()]);
    }

    public function addFromSamolit(string $content): bool
    {
        /** @var SamolitBook $parsedBook */
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

    public function create(array $params): Book
    {
        try {
            DB::beginTransaction();

            /** @var BookManager $booksManager */
            $booksManager = app(BookManager::class);
            $book = $booksManager->create($params);
            $booksManager->addGenres($params['genres']);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        if ($book->description && $book->status === Book::ACTIVE_STATUS) {
            BookDescriptionUpdated::dispatch($book);
        }

        return $book;
    }

    public function addFromTelegram(array $chatData, int $telegramId)
    {
        try {
            DB::beginTransaction();
            $newBook = false;

            if (isset($chatData['selectedBookId'])) {
                $book = Book::find($chatData['selectedBookId']);
                $this->bookManager->book = $book;
            } else {
                $title = $chatData['title'];
                $author = $chatData['author'];
                $newBook = !$this->bookManager->checkBookExists($title, $author);

                $book = $this->bookManager->firstOrCreate(['title' => $title, 'author' => $author]);
            }

            /** @var TelegramUser $telegramUser */
            $telegramUser = TelegramUser::findByTelegramId($telegramId);
            app(TelegramUserBookManager::class)
                ->createOrUpdate(['rating' => $chatData['bookRating']], $telegramUser, $book);

            if (isset($chatData['associations'])) {
                app(AssociationManager::class)
                    ->addForTelegramUser($chatData['associations'], $book, $telegramUser);
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        if ($newBook) {
            $user = "{$telegramUser->first_name} {$telegramUser->last_name} ({$telegramUser->username})";
            $text = "Пользователь $user добавил новую книгу:\n";
            $text .= "{$book->author} - {$book->title}";

            $this->notificationService->notify($text);
        }
    }
}
