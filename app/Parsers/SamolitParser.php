<?php

namespace App\Parsers;

use App\Entities\SamolitBook;

class SamolitParser
{
    public function getBook(string $content): SamolitBook
    {
        $parsedBook = new SamolitBook();

        $parsedBook->title = $this->getTitle($content);
        $parsedBook->description = $this->getDescription($content);
        $parsedBook->author = $this->getAuthor($content);
        $parsedBook->genres = $this->getGenres($content);

        return $parsedBook;
    }

    private function getTitle(string $content): string
    {
        preg_match('/<h1 itemprop="name" data-widget-litres-book>(.*?)<\/h1>/', $content, $titleMatches);
        return $titleMatches[1];
    }

    private function getDescription(string $content): string
    {
        preg_match(
            '/<div class="book-description padding-xs-5" itemprop="text">(.*?)<\/div>/ms',
            $content,
            $descriptionMatches
        );
        return trim($descriptionMatches[1]);
    }

    private function getAuthor(string $content): string
    {
        preg_match(
            '/<a href="(.*?)" class="author" itemprop="author" data-widget-litres-author>(.*?)<\/a>/',
            $content,
            $authorMatches
        );

        return $authorMatches[2];
    }

    private function getGenres(string $content): array
    {
        preg_match(
            '/<span class="small-grey" itemprop="genre">Жанры:<\/span>(.*?)<br/',
            $content,
            $genreMatches
        );
        $genresString = $genreMatches[1];

        preg_match_all(
            '/<a href="\/genres\/(.*?)\/"(.*?)>(.*?)<\/a>/',
            $genresString,
            $genres
        );
        return $genres[3];
    }
}
