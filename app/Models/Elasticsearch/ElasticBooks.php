<?php

namespace App\Models\Elasticsearch;

use App\Support\ElasticQuery;

class ElasticBooks extends ElasticModel
{
    public string $alias = 'books';

    public function getIndexQuery(string $index = null): ElasticQuery
    {
        $body = $this->getIndexTemplate('books');

        return new ElasticQuery(
            $index ?? $this->alias,
            json_decode($body, true)
        );
    }

    public function getIndexLoadQuery(array $data, string $index = null): ElasticQuery
    {
        $body = self::getBodyForBulkAdd($data, $index ?? $this->alias);

        return new ElasticQuery(
            $index ?? $this->alias,
            $body
        );
    }

    public function getSearchQuery(string $query, int $size = 10): ElasticQuery
    {
        $template = $this->getSearchTemplate('booksQuery');
        $template = str_replace("{{query}}", $query, $template);
        $template = str_replace("{{size}}", $size, $template);

        return new ElasticQuery(
            $this->alias,
            json_decode($template, true)
        );
    }

    public function getTitleAuthorQuery(string $title, string $author, int $size = 10): ElasticQuery
    {
        $template = $this->getSearchTemplate('booksShort');
        $template = str_replace("{{title}}", $title, $template);
        $template = str_replace("{{author}}", $author, $template);
        $template = str_replace("{{size}}", $size, $template);

        return new ElasticQuery(
            $this->alias,
            json_decode($template, true)
        );
    }
}
