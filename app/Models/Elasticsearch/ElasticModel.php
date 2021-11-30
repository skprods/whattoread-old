<?php

namespace App\Models\Elasticsearch;

abstract class ElasticModel
{
    public string $alias = '';

    protected function getIndexTemplate(string $template): string
    {
        $path = resource_path("elasticsearch/index/{$template}.json");
        return file_get_contents($path);
    }

    protected function getSearchTemplate(string $template): string
    {
        $path = resource_path("elasticsearch/search/{$template}.json");
        return file_get_contents($path);
    }

    public static function getBodyForBulkAdd(array $data, string $index): array
    {
        $params = [];

        foreach ($data as $item) {
            $params[] = [
                'index' => [
                    '_index' => $index,
                    '_id' => $item['id'],
                ],
            ];

            $params[] = $item;
        }

        return $params;
    }
}
