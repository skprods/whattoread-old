<?php

namespace App\Managers;

use App\Models\Book;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Console;

class BooksManager
{
    private BookManager $bookManager;

    public function __construct()
    {
        $this->bookManager = app(BookManager::class);
    }

    public function addFromBukvoed(string $content, string $url, int $shopBookId): bool
    {
        $book = (object) [];

        if (!$this->checkBookCategory($content)) {
            return false;
        }

        $content = str_replace("\r", ' ', $content);

        preg_match('/<h1><span itemprop="name">(.*?)<\/span>/', $content, $matches);
        $book->title = $matches[1] ?? null;
        if (!$book->title) {
            preg_match('/<h1 class="bC"><span itemprop="name" class="cC">(.*?)<span/', $content, $matches);
            $book->title = $matches[1] ?? null;
        }

        preg_match('/<div class="Pz Zv">\n(.*?)<div/', $content, $matches);
        $description = $matches[1] ?? null;
        if (!$description) {
            preg_match('/<div class="mC">\n(.*?)<\/div/', $content, $matches);
            $description = $matches[1] ?? null;
        }
        $book->description = ($description) ? trim($description) : $description;

        preg_match_all('/<tr class="Yz">(.*?)<\/tr>/', $content, $matchesAll);
        if (count($matchesAll) === 2) {
            preg_match_all('/<tr class="Xz">(.*?)<\/tr>/', $content, $matchesAll);
        }

        foreach ($matchesAll[0] as $row) {
            $this->setBookInfo($row, $book);
        }

        if (!property_exists($book, 'author')) {
            Console::info("Поля с автором не найдено.");
            return false;
        }

        $book->shop_url = $url;
        $book->shop_name = Book::SHOP_BUKVOED;
        $book->shop_book_id = $shopBookId;

        try {
            $this->bookManager->create((array) $book);
        } catch (\Exception $exception) {
            dump($book);
            Log::error(json_encode($book));
            throw $exception;
        }

        unset($book);
        return true;
    }

    private function checkBookCategory(string $content): bool
    {
        $pattern = '/<a itemprop="item" href=(.*?)https:\/\/www.bookvoed.ru\/books\?genre=2(.*?)><span itemprop="name">Книги<\/span><\/a>/';
        preg_match($pattern, $content, $matches);

        return (bool) count($matches);
    }

    private function setBookInfo(string $row, object $book)
    {
        preg_match('/<tr class="Yz">(.*?)<\/tr>/', $row, $matches);
        if (!count($matches)) {
            preg_match('/<tr class="Xz">(.*?)<\/tr>/', $row, $matches);
        }

        $cols = $matches[1];
        preg_match('/<td class="Zz">(.*?)<\/td>/', $cols, $mc);
        if (!$mc) {
            preg_match('/<td class="Vz">(.*?)<\/td>/', $cols, $mc);
        }

        if (str_contains($mc[1], 'Автор')) {
            $book->author = $this->getPropInfo($cols);
        }
        if (str_contains($mc[1], 'Тематика')) {
            $book->category = $this->getPropInfo($cols);
        }
        if (str_contains($mc[1], 'Серия')) {
            $book->series = $this->getPropInfo($cols);
        }
        if (str_contains($mc[1], 'Издательство')) {
            $book->publisher_name = $this->getPropInfo($cols);
        }
        if (str_contains($mc[1], 'Год')) {
            $book->publisher_year = $this->getPropInfo($cols);
        }
    }

    private function getPropInfo(string $columns): string
    {
        preg_match('/<td class="aA">(.*?)<\/td>/', $columns, $contentMatches);
        if (!count($contentMatches)) {
            preg_match('/<td class="Wz">(.*?)<\/td>/', $columns, $contentMatches);
        }

        preg_match('/<(.*?)>(.*?)<(.*?)>/', $contentMatches[1], $content);

        if (count($content)) {
            return trim($content[2]);
        } else {
            return trim($contentMatches[1]);
        }
    }
}
