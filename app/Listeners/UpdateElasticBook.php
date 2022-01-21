<?php

namespace App\Listeners;

use App\Events\BookUpdated;
use App\Models\Book;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;

class UpdateElasticBook extends Listener
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
        $this->identifier = $event->book->id;
        $this->log("Начинается актуализация книги #{$event->book->id} в поисковом индексе.");

        $index = $this->service->getLastIndexByAlias($this->model->alias);
        $id = $event->book->id;
        $data = $event->book->toArray();

        if ($event->book->status === Book::MODERATION_STATUS) {
            $this->log("Книга #{$event->book->id} в статусе {$event->book->status}. Начинается удаление из индекса.");
            $this->service->delete($index, $id);
            $this->log("Книга #{$event->book->id} удалена из поискового индекса.");
        } else {
            $this->log("Добавляем/обновляем книгу #{$event->book->id} в поисковом индексе.");
            $this->service->createOrUpdate($index, $id, $data);
            $this->log("Книга #{$event->book->id} успешно сохранена в поисковом индексе.");
        }
    }
}
