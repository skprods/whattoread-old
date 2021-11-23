<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\ResourceCollections\Admin\BooksCollection;
use App\Http\ResourceCollections\CollectionResource;
use App\Models\Book;
use App\Traits\HasDataTableFilters;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;

class BooksController extends Controller
{
    use HasDataTableFilters;

    public function index(): CollectionResource
    {
        $dataTable = DataTables::eloquent(Book::query())
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
}
