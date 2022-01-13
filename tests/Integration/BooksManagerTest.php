<?php

namespace Tests\Integration;

use App\Entities\ChatInfo;
use App\Managers\BooksManager;
use App\Models\Book;
use App\Models\Genre;
use App\Models\TelegramUser;
use Tests\TestCase;

class BooksManagerTest extends TestCase
{
    public function testAddFromTelegram()
    {
        $telegramUser = TelegramUser::factory()->create();
        $chatData = $this->getChatData();
        $chatInfo = new ChatInfo($telegramUser->telegram_id, $chatData);

        app(BooksManager::class)->addFromTelegram($chatInfo);

        $this->assertBooksHas($chatData, Book::MODERATION_STATUS);
        $this->assertGenresHas($chatData);
        $this->assertTelegramUserBooksHas($chatData, $telegramUser->id);
        $this->assertAssociationsHas($chatData, $telegramUser->id);
    }

    public function testAddFromTelegramWithExistingBook()
    {
        $telegramUser = TelegramUser::factory()->create();
        $chatData = $this->getChatData();
        $book = Book::factory()->create([
            'title' => $chatData['dialog']['messages']['title'][0],
            'author' => $chatData['dialog']['messages']['author'][0],
            'status' => Book::ACTIVE_STATUS,
        ]);
        $chatData['dialog'] = array_merge($chatData['dialog'], ['selectedBookId' => $book->id]);
        $chatInfo = new ChatInfo($telegramUser->telegram_id, $chatData);

        app(BooksManager::class)->addFromTelegram($chatInfo);

        $this->assertBooksHas($chatData, Book::ACTIVE_STATUS, $book->id);
        $this->assertGenresHas($chatData);
        $this->assertTelegramUserBooksHas($chatData, $telegramUser->id, $book->id);
        $this->assertAssociationsHas($chatData, $telegramUser->id, $book->id);
    }

    private function getChatData(): array
    {
        return [
            'dialog' => [
                'messages' => [
                    'title' => [ 'WhatToRead History' ],
                    'author' => [ 'Pavel Sklyar' ],
                    'genres' => ['история', 'автобиография'],
                    'associations' => ['путь к успеху', 'история успеха', 'бизнес', 'программирование'],
                ],
                'bookRating' => 5,
            ],
        ];
    }

    private function assertBooksHas(array $chatData, string $status, int $bookId = null)
    {
        $data = [
            'title' => $chatData['dialog']['messages']['title'][0],
            'author' => $chatData['dialog']['messages']['author'][0],
            'status' => $status,
        ];

        if ($bookId) {
            $data['id'] = $bookId;
        }

        $this->assertDatabaseHas('books', $data);
    }

    public function assertGenresHas(array $chatData)
    {
        foreach ($chatData['dialog']['messages']['genres'] as $genre) {
            $this->assertDatabaseHas('genres', [
                'name' => $genre,
                'status' => Genre::MODERATION_STATUS,
            ]);
        }
    }

    public function assertTelegramUserBooksHas(array $chatData, int $telegramUserId, int $bookId = null)
    {
        $data = [
            'telegram_user_id' => $telegramUserId,
            'rating' => $chatData['dialog']['bookRating'],
        ];

        if ($bookId) {
            $data['book_id'] = $bookId;
        }

        $this->assertDatabaseHas('telegram_user_books', $data);
    }

    public function assertAssociationsHas(array $chatData, int $telegramUserId, int $bookId = null)
    {
        foreach ($chatData['dialog']['messages']['associations'] as $association) {
            $userBook = [
                'telegram_user_id' => $telegramUserId,
                'association' => $association,
            ];
            if ($bookId) {
                $userBook['book_id'] = $bookId;
            }
            $this->assertDatabaseHas('user_book_associations', $userBook);

            $associations = [
                'association' => $association,
                'total' => 1,
            ];
            if ($bookId) {
                $associations['book_id'] = $bookId;
            }
            $this->assertDatabaseHas('book_associations', $associations);
        }
    }
}
