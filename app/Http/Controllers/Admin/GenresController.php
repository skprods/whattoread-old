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
    /**
     * @OA\Get(
     *     path="api/v1/admin/genres",
     *     summary="Получение жанров",
     *     description="Получение информации о всех жанрах",
     *     operationId="admin.genres",
     *     tags={"Админ-панель | Жанры"},
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
     *         @OA\JsonContent(ref="#/components/schemas/GenreResource")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не авторизован",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedException")
     *     ),
     * )
     */
    public function index(Request $request): CollectionResource
    {
        $builder = Genre::query()->with(['parents', 'childs']);

        $dataTable = DataTables::eloquent($builder);

        return GenresCollection::fromDataTable($dataTable, $request->paginate ?? null);
    }
}
