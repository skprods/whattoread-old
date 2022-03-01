<?php

namespace App\Http\Controllers\Admin;

use App\Http\Collections\Admin\RecsCollection;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookRecsShort;
use App\Traits\HasDataTableFilters;

class RecsController extends Controller
{
    use HasDataTableFilters;

    /**
     * @OA\Get(
     *     path="api/v1/admin/books/{id}/recs",
     *     summary="Рекомендации для книги",
     *     description="Получение совпадающих книг",
     *     operationId="admin.books.recs",
     *     tags={"Админ-панель | Книги"},
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {token}",
     *         @OA\Schema(
     *             type="bearerAuth"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная выборка",
     *         @OA\JsonContent(ref="#/components/schemas/RecsCollection")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function byBook(BookRecsShort $bookRecsShort): RecsCollection
    {
        $comparingBook = $bookRecsShort->book;
        $books = $bookRecsShort->getMatchingBooks();
        $matchingData = $bookRecsShort->data;

        $books = $books->map(function (Book $book) use ($comparingBook, $matchingData) {
            return (object) array_merge([
                'book' => $comparingBook,
                'matching_book' => $book,
            ], $matchingData[$book->id]);
        })->sortByDesc('total_score');

        return new RecsCollection($books);
    }
}