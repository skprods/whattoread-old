<?php

namespace App\Managers;

use App\Models\Book;
use App\Models\TelegramUser;
use App\Models\TelegramUserBook;

class TelegramUserBookManager
{
    private ?TelegramUserBook $telegramUserBook;

    public function __construct(TelegramUserBook $telegramUserBook = null)
    {
        $this->telegramUserBook = $telegramUserBook;
    }

    public function createOrUpdate(array $params, TelegramUser $telegramUser, Book $book): TelegramUserBook
    {
        $this->telegramUserBook = TelegramUserBook::query()->where('book_id', $book->id)->first();

        if ($this->telegramUserBook) {
            return $this->update($params);
        } else {
            return $this->create($params, $telegramUser, $book);
        }
    }

    public function create(array $params, TelegramUser $telegramUser, Book $book): TelegramUserBook
    {
        $this->telegramUserBook = app(TelegramUserBook::class);
        $this->telegramUserBook->fill($params);
        $this->telegramUserBook->telegramUser()->associate($telegramUser);
        $this->telegramUserBook->book()->associate($book);
        $this->telegramUserBook->save();

        return $this->telegramUserBook;
    }

    public function update(array $params): TelegramUserBook
    {
        $this->telegramUserBook->fill($params);
        $this->telegramUserBook->save();

        return $this->telegramUserBook;
    }
}
