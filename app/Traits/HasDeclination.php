<?php

namespace App\Traits;

trait HasDeclination
{
    public function getBooksDeclination(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count книга";
            case 2:
            case 3:
            case 4:
                return "$count книги";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count книг";
            default:
                return "";
        }
    }

    public function getAuthorDeclination(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count автор";
            case 2:
            case 3:
            case 4:
                return "$count автора";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count авторов";
            default:
                return "";
        }
    }

    public function getGenresDeclination(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count жанр";
            case 2:
            case 3:
            case 4:
                return "$count жанра";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count жанров";
            default:
                return "";
        }
    }

    public function getAssociationsDeclination(int $count): string
    {
        switch ($count % 10) {
            case 1:
                return "$count книжная ассоциация";
            case 2:
            case 3:
            case 4:
                return "$count книжные ассоциации";
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 0:
                return "$count книжных ассоциаций";
            default:
                return "";
        }
    }
}