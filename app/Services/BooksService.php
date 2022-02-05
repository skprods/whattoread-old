<?php

namespace App\Services;

use App\Events\BookDescriptionUpdated;
use App\Managers\AssociationManager;
use App\Managers\BookManager;
use App\Managers\TelegramUserBookManager;
use App\Models\Book;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\DB;
use SKprods\Telegram\Core\Telegram;

class BooksService
{
    private BookManager $bookManager;
    private NotificationService $notificationService;

    public function __construct()
    {
        $this->bookManager = app(BookManager::class);
        $this->notificationService = app(NotificationService::class, ['telegram' => new Telegram()]);
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

    /**
     * $chatData = [
     *   'title' => 'Название книги',
     *   'author' => 'Автор книги',
     *   'selectedBookId' => 'ID выбранной книги, если такая уже существует',
     *   'bookRating' => 'Рейтинг книги от 1 до 5 (int)',
     *   'associations' => ['Массив строк-ассоциаций с книгой, может быть пустым'],
     * ]
     */
    public function createFromTelegram(array $chatData, int $telegramId)
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
