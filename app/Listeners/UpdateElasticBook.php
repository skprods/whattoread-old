<?php

namespace App\Listeners;

use App\Events\BookUpdated;
use App\Models\Book;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateElasticBook implements ShouldQueue
{
    private ElasticsearchService $service;
    private ElasticBooks $model;

    public function __construct()
    {
        $this->service = app(ElasticsearchService::class);
        $this->model = app(ElasticBooks::class);
    }

    public function handle(BookUpdated $event)
    {
        $index = $this->service->getLastIndexByAlias($this->model->alias);
        $id = $event->book->id;
        $data = $event->book->toArray();

        if ($event->book->status === Book::MODERATION_STATUS) {
            $this->service->delete($index, $id);
        } else {
            $this->service->createOrUpdate($index, $id, $data);
        }
    }
}
