<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;
use Illuminate\Database\Eloquent\Collection;

class ReindexBooksJob extends QueueJob
{
    private ElasticsearchService $service;
    private ElasticBooks $model;

    public function __construct(bool $debug)
    {
        parent::__construct($debug);

        $this->service = app(ElasticsearchService::class);
        $this->model = app(ElasticBooks::class);
    }

    public function handle()
    {
        $time = time();
        $this->log("Начинается переиндексация книг...");

        $lastIndex = $this->service->getLastIndexByAlias($this->model->alias);
        $this->log("Последний индекс: $lastIndex");

        if ($lastIndex) {
            $identifier = explode('_', $lastIndex)[1];
            $newIndex = $this->model->alias . "_" . ++$identifier;
        } else {
            $newIndex = "{$this->model->alias}_1";
        }

        $this->log("Новый индекс: $newIndex");

        $createIndexQuery = $this->model->getIndexQuery($newIndex);

        $this->log("Создание нового индекса...");
        $this->service->createIndex($createIndexQuery);
        $this->log("Создание индекса завершено.");

        Book::query()
            ->where('status', Book::ACTIVE_STATUS)
            ->chunk(10000, function (Collection $data) use ($newIndex) {
                $this->log("Вставка данных в индекс $newIndex: {$data->count()} строк...");

                $loadIndexQuery = $this->model->getIndexLoadQuery($data->toArray(), $newIndex);
                $this->service->bulkAdd($loadIndexQuery);
                unset($loadIndexQuery);

                $count = $this->service->getIndexSize($newIndex);
                $this->log("Вставка данных завершена. Размер индекса: $count");
            });

        $this->log("Объединение индекса {$newIndex} с алиасом {$this->model->alias}...");
        $this->service->createAlias($this->model->alias, $newIndex);
        $this->log("Объединение индекса с алиасом завершено.");

        if ($lastIndex) {
            $this->log("Удаление индекса $lastIndex...");
            $this->service->deleteIndexIfExists($lastIndex);
        }

        $workTime = time() - $time;
        $this->log("Переиндексация {$this->model->alias} завершена за {$workTime}с.");
    }
}
