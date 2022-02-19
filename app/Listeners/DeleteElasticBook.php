<?php

namespace App\Listeners;

use App\Events\BookDeleted;
use App\Exceptions\ElasticBookNotDeleted;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;

class DeleteElasticBook extends Listener
{
    private ElasticsearchService $service;
    private ElasticBooks $model;

    public function __construct()
    {
        $this->service = app(ElasticsearchService::class);
        $this->model = app(ElasticBooks::class);
    }

    /**
     * @throws ElasticBookNotDeleted
     */
    public function handle(BookDeleted $event)
    {
        $this->identifier = $event->bookId;
        $this->log("Начинается удаление книги #{$event->bookId} из поискового индекса.");

        $index = $this->service->getLastIndexByAlias($this->model->alias);
        $id = $event->bookId;
        $booksResult = $this->service->getById($index, $id);

        if ($booksResult) {
            $res = $this->service->delete($index, $id);

            if (!$res) {
                throw new ElasticBookNotDeleted($id);
            }
        }

        $this->log("Книга #{$event->bookId} успешно удалена из поискового индекса.");
    }
}
