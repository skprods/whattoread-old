<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewFrequencies;
use App\Http\Controllers\Controller;
use App\Http\Collections\Admin\BooksCollection;
use App\Http\Collections\CollectionResource;
use App\Http\Requests\Admin\BookCreateRequest;
use App\Http\Requests\Admin\BookUpdateRequest;
use App\Http\Resources\Admin\BookResource;
use App\Http\Resources\SingleResource;
use App\Managers\BookManager;
use App\Managers\BooksManager;
use App\Managers\FileManager;
use App\Models\Book;
use App\Traits\HasDataTableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;

class BooksController extends Controller
{
    use HasDataTableFilters;

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
                        return $query->select("therm_frequencies.id")
                            ->from('therm_frequencies')
                            ->whereRaw('therm_frequencies.book_id = books.id');
                    }),
                    "not-exists" => $query->whereNotExists(function (\Illuminate\Database\Query\Builder $query) {
                        return $query->select("therm_frequencies.id")
                            ->from('therm_frequencies')
                            ->whereRaw('therm_frequencies.book_id = books.id');
                    }),
                    default => $query,
                };
            })
            ->filterColumn(...$this->filterDate('created_at'));

        return BooksCollection::fromDataTable($dataTable, 30);
    }

    public function show(Book $book): BookResource
    {
        return new BookResource($book->load(['genres']));
    }

    public function create(BookCreateRequest $request): BookResource
    {
        $book = app(BooksManager::class)->create($request->validated());

        return new BookResource($book->load('genres'));
    }

    public function update(BookUpdateRequest $request, Book $book): BookResource
    {
        $book = app(BookManager::class, ['book' => $book])->update($request->validated());

        return new BookResource($book->load('genres'));
    }

    public function delete(Book $book): Response
    {
        app(BookManager::class, ['book' => $book])->delete();

        return response()->noContent();
    }

    public function createFrequency(Request $request, Book $book): SingleResource
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:text/xml'],
        ]);

        $filepath = app(FileManager::class)->saveBookFile($request->file('file'));
        NewFrequencies::dispatch($filepath, $book->id);

        return new SingleResource([
            'message' => "Файл успешно загружен, а частотный словник будет составлен в фоне.",
        ]);
    }
}
