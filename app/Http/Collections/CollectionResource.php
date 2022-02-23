<?php

namespace App\Http\Collections;

use App\Traits\CanPrepareData;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Yajra\DataTables\EloquentDataTable;

/**
 * @OA\Schema(
 *     description="Коллекция записей",
 *     @OA\Property( property="success", type="boolean", example="true", description="Успешный ли ответ" ),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         description="Пагинация",
 *         @OA\Property( property="per_page", type="integer", example="30",
 *             description="Количество записей на страницу"
 *         ),
 *         @OA\Property( property="total", type="integer", example="120", description="Всего записей" ),
 *         @OA\Property( property="current_page", type="integer", example="3", description="Текущая страница" ),
 *         @OA\Property( property="first_page", type="integer", example="1", description="Первая страница" ),
 *         @OA\Property( property="last_page", type="integer", example="4", description="Последняя страница" ),
 *         @OA\Property( property="prev_page", type="integer", example="2", description="Предыдущая страница" ),
 *         @OA\Property( property="next_page", type="integer", example="4", description="Следующая страница" ),
 *     ),
 * )
 */
class CollectionResource extends ResourceCollection
{
    use CanPrepareData;

    public $with = [
        'success' => true,
    ];

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);
        $jsonResponse = $this->preparePagination($jsonResponse);
        $response->setContent(json_encode($jsonResponse));
    }

    private function preparePagination(array $jsonResponse): array
    {
        if (isset($jsonResponse['links']) && $jsonResponse['meta']) {
            $jsonResponse['pagination'] = [
                'per_page' => (int) $jsonResponse['meta']['per_page'],
                'total' => $jsonResponse['meta']['total'],
                'current_page' => $jsonResponse['meta']['current_page'],
                'first_page' => $this->getPageFromLink($jsonResponse['links']['first']),
                'last_page' => $this->getPageFromLink($jsonResponse['links']['last']),
                'prev_page' => $this->getPageFromLink($jsonResponse['links']['prev']),
                'next_page' => $this->getPageFromLink($jsonResponse['links']['next']),
            ];

            unset($jsonResponse['links'], $jsonResponse['meta']);
        }

        return $jsonResponse;
    }

    private function getPageFromLink(?string $link): ?int
    {
        if (is_null($link)) {
            return null;
        }

        $exploded = explode('=', $link);
        return $exploded[1] ?? null;
    }

    public static function fromDataTable(EloquentDataTable $dataTable, int $paginate = null): CollectionResource
    {
        if ($paginate) {
            return new static($dataTable->getFilteredQuery()->paginate($paginate));
        } else {
            return new static($dataTable->getFilteredQuery()->get());
        }
    }
}
