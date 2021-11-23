<?php

namespace App\Http\ResourceCollections;

use App\Traits\CanPrepareData;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Yajra\DataTables\EloquentDataTable;

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

    public static function fromDataTable(EloquentDataTable $dataTable, int $paginate): CollectionResource
    {
        return new static($dataTable->getFilteredQuery()->paginate($paginate));
    }
}
