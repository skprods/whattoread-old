<?php

namespace App\Managers;

use App\Events\BookDeleted;
use App\Events\BookUpdated;
use App\Models\Book;
use App\Models\Stat;
use Exception;
use Illuminate\Support\Facades\DB;

class BookManager
{
    public ?Book $book;
    private StatManager $statManager;

    public function __construct(Book $book = null)
    {
        $this->book = $book;
        $this->statManager = app(StatManager::class);
    }

    public function firstOrCreate(array $params): Book
    {
        $this->book = Book::query()
            ->where('title', '=', $params['title'])
            ->where('author', '=', $params['author'])
            ->first();

        if ($this->book) {
            return $this->book;
        } else {
            return $this->create($params);
        }
    }

    public function createOrUpdate(array $params): Book
    {
        $this->book = Book::query()
            ->where('title', '=', $params['title'])
            ->where('author', '=', $params['author'])
            ->first();

        if ($this->book) {
            return $this->update($params);
        } else {
            return $this->create($params);
        }
    }

    public function create(array $params): Book
    {
        $this->book = app(Book::class);
        $this->book->fill($params);
        $this->book->save();

        $this->statManager->create(Stat::BOOK_MODEL, $this->book->id, Stat::CREATED_ACTION);

        return $this->book;
    }

    public function update(array $params): Book
    {
        $this->book->fill($params);
        $this->book->save();

        if (isset($params['genres'])) {
            $this->book->genres()->sync($params['genres']);
        }

        BookUpdated::dispatch($this->book);

        return $this->book;
    }

    public function delete(): ?bool
    {
        try {
            DB::beginTransaction();
            $this->book->genres()->sync([]);
            $this->book->categories()->sync([]);
            $this->book->telegramUsers()->delete();
            $this->book->associations()->delete();
            $this->book->userAssociations()->delete();

            BookDeleted::dispatch($this->book->id);

            $delete = $this->book->delete();
            $this->statManager->create(Stat::BOOK_MODEL, $this->book->id, Stat::DELETED_ACTION);

            DB::commit();

            return $delete;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function addGenres(array $genreIds)
    {
        $genres = $this->book->genres()->pluck('id')->toArray();
        $this->book->genres()->sync(array_merge($genres, $genreIds));
    }
}
