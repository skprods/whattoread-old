<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Collections\Admin\BooksCollection;
use App\Http\Collections\CollectionResource;
use App\Http\Requests\Admin\BookUpdateRequest;
use App\Http\Resources\Admin\BookResource;
use App\Managers\BookManager;
use App\Models\Book;
use App\Traits\HasDataTableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;

class BooksController extends Controller
{
    use HasDataTableFilters;

    public function index(): CollectionResource
    {
        $builder = Book::query()->with('genres');

        $dataTable = DataTables::eloquent($builder)
            ->filterColumn(...$this->filterInteger('id'))
            ->filterColumn('status', function (Builder $query, $keyword) {
                if ($keyword === 'all') {
                    return $query;
                }

                return $query->where('status', $keyword);
            })
            ->filterColumn(...$this->filterDate('created_at'));

        return BooksCollection::fromDataTable($dataTable, 30);
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
}
