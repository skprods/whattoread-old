<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';

    protected $description = 'Проверка работоспособности всех частей приложения';

    public function handle()
    {
        /** Проверка ElasticSearch */
        $elasticSearchService = app(ElasticsearchService::class);
        $elasticSearchService->client->indices()->exists([
            'index' => 'healthCheck',
        ]);

        /** Проверка Redis */
        Redis::connection('default');

        /** Проверка Percona */
        Book::first();
    }
}
