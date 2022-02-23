<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewFrequencies;
use App\Http\Collections\Admin\BooksCollection;
use App\Http\Collections\CollectionResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookCreateRequest;
use App\Http\Requests\Admin\BookUpdateRequest;
use App\Http\Resources\Admin\BookResource;
use App\Http\Resources\SingleResource;
use App\Managers\BookManager;
use App\Models\Book;
use App\Services\BooksService;
use App\Services\FileService;
use App\Traits\HasDataTableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;

class BooksController extends Controller
{
    use HasDataTableFilters;

    /**
     * @OA\Get(
     *     path="api/v1/admin/books",
     *     summary="Получение книг",
     *     description="Получение информации о всех книгах с пагинацией",
     *     operationId="admin.books",
     *     tags={"Админ-панель | Книги"},
     *     security={ {"bearer":{}} },
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {token}",
     *         @OA\Schema(
     *             type="bearerAuth"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="pagination",
     *         in="query",
     *         example="30",
     *         description="Число записей на страницу"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная выборка",
     *         @OA\JsonContent(ref="#/components/schemas/BooksCollection")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function index(): CollectionResource
    {
        $builder = Book::query()->with(['genres']);

        $dataTable = DataTables::eloquent($builder)
            ->filterColumn(...$this->filterInteger('id'))
            ->filterColumn('status', function (Builder $query, $keyword) {
                if ($keyword === 'all') {
                    return $query;
                }

                return $query->where('status', $keyword);
            })
            ->filterColumn('frequency', function (Builder $query, $keyword) {
                return match ($keyword) {
                    "exists" => $query->whereExists(function (\Illuminate\Database\Query\Builder $query) {
                        return $query->select("book_content_frequencies.id")
                            ->from('book_content_frequencies')
                            ->whereRaw('book_content_frequencies.book_id = books.id');
                    }),
                    "not-exists" => $query->whereNotExists(function (\Illuminate\Database\Query\Builder $query) {
                        return $query->select("book_content_frequencies.id")
                            ->from('book_content_frequencies')
                            ->whereRaw('book_content_frequencies.book_id = books.id');
                    }),
                    default => $query,
                };
            })
            ->filterColumn(...$this->filterDate('created_at'));

        return BooksCollection::fromDataTable($dataTable, 30);
    }

    /**
     * @OA\Get(
     *     path="api/v1/admin/books/{id}",
     *     summary="Получение книги",
     *     description="Получение информации о конкретной книги по ID",
     *     operationId="admin.books.show",
     *     tags={"Админ-панель | Книги"},
     *     security={ {"bearer":{}} },
     *     @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {token}",
     *         @OA\Schema(
     *             type="bearerAuth"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         example="1",
     *         description="ID нужной книги"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная выборка",
     *         @OA\JsonContent(ref="#/components/schemas/BookResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function show(Book $book): BookResource
    {
        return new BookResource($book->load(['genres']));
    }

    /**
     * @OA\Post(
     *     path="api/v1/admin/books",
     *     summary="Создание книги",
     *     description="Создание книги",
     *     operationId="admin.books.create",
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
     *     @OA\RequestBody(ref="#/components/requestBodies/BookCreateRequest"),
     *     @OA\Response(
     *         response=201,
     *         description="Книга создана",
     *         @OA\JsonContent(ref="#/components/schemas/BookResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function create(BookCreateRequest $request): BookResource
    {
        $book = app(BooksService::class)->create($request->validated());

        return new BookResource($book->load('genres'));
    }

    /**
     * @OA\Put(
     *     path="api/v1/admin/books/{id}",
     *     summary="Обновление книги",
     *     description="Обновление книги",
     *     operationId="admin.books.update",
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         example="1",
     *         description="ID нужной книги"
     *     ),
     *     @OA\RequestBody(ref="#/components/requestBodies/BookUpdateRequest"),
     *     @OA\Response(
     *         response=200,
     *         description="Книга обновлена",
     *         @OA\JsonContent(ref="#/components/schemas/BookResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function update(BookUpdateRequest $request, Book $book): BookResource
    {
        $book = app(BookManager::class, ['book' => $book])->update($request->validated());

        return new BookResource($book->load('genres'));
    }

    /**
     * @OA\Delete(
     *     path="api/v1/admin/books/{id}",
     *     summary="Удаление книги",
     *     description="Удаление книги",
     *     operationId="admin.books.delete",
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         example="1",
     *         description="ID нужной книги"
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Книга удалена",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function delete(Book $book): Response
    {
        app(BookManager::class, ['book' => $book])->delete();

        return response()->noContent();
    }

    /**
     * @OA\Post(
     *     path="api/v1/admin/books/{id}/frequency",
     *     summary="Создание книги",
     *     description="Создание книги",
     *     operationId="admin.books.createFrequency",
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         example="1",
     *         description="ID нужной книги"
     *     ),
     *     @OA\RequestBody(
     *         request="CreateFrequency",
     *         required=true,
     *         description="Запрос на создание книги",
     *         @OA\JsonContent(
     *             required={"file"},
     *             @OA\Property( property="string", type="string", format="binary", example="book.fb2",
     *                 description="Файл книги в формате fb2"
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Книга создана",
     *         @OA\JsonContent(ref="#/components/schemas/BookResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function createFrequency(Request $request, Book $book): SingleResource
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/xml'],
        ]);

        $filepath = app(FileService::class)->saveBookFile($request->file('file'));
        NewFrequencies::dispatch($filepath, $book->id);

        return new SingleResource([
            'message' => "Файл успешно загружен, а частотный словник будет составлен в фоне.",
        ]);
    }
}
