<?php

namespace App\Http\Controllers\Admin;

use App\Http\Collections\Admin\GenresCollection;
use App\Http\Collections\CollectionResource;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GenresController extends Controller
{
    public function index(Request $request): CollectionResource
    {
        $builder = Genre::query();

        $dataTable = DataTables::eloquent($builder);

        return GenresCollection::fromDataTable($dataTable, $request->paginate ?? null);
    }
}
