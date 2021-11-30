<?php

namespace App\Listeners;

use App\Events\BookDeleted;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteElasticBook implements ShouldQueue
{
    private ElasticsearchService $service;
    private ElasticBooks $model;

    public function __construct()
    {
        $this->service = app(ElasticsearchService::class);
        $this->model = app(ElasticBooks::class);
    }

    public function handle(BookDeleted $event)
    {
        $index = $this->service->getLastIndexByAlias($this->model->alias);
        $id = $event->bookId;

        $this->service->delete($index, $id);
    }
}
