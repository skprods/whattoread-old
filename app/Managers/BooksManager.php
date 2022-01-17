<?php

namespace App\Managers;

use App\Entities\ChatInfo;
use App\Entities\SamolitBook;
use App\Models\Book;
use App\Models\TelegramUser;
use App\Parsers\SamolitParser;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api;

class BooksManager
{
    private BookManager $bookManager;
    private GenreManager $genreManager;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->bookManager = app(BookManager::class);
        $this->genreManager = app(GenreManager::class);

        $token = config('telegram.bots.whattoread.token');
        $this->notificationService = app(NotificationService::class, ['telegram' => new Api($token)]);
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

        return $book;
    }

    public function addFromTelegram(ChatInfo $chatInfo)
    {
        try {
            DB::beginTransaction();
            $dialog = $chatInfo->dialog;
            $newBook = false;

            if ($dialog->selectedBookId) {
                $book = Book::find($dialog->selectedBookId);
                $this->bookManager->book = $book;
            } else {
                $title = $dialog->messages['title'][0];
                $author = $dialog->messages['author'][0];
                $newBook = !$this->bookManager->checkBookExists($title, $author);

                $book = $this->bookManager->firstOrCreate(['title' => $title, 'author' => $author]);
            }

            /** @var TelegramUser $telegramUser */
            $telegramUser = TelegramUser::findByTelegramId($chatInfo->id);
            app(TelegramUserBookManager::class)
                ->createOrUpdate(['rating' => $dialog->bookRating], $telegramUser, $book);

            if (isset($dialog->messages['associations'])) {
                app(AssociationManager::class)
                    ->addForTelegramUser($dialog->messages['associations'], $book, $telegramUser);
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
