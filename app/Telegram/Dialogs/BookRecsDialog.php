<?php

namespace App\Telegram\Dialogs;

use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;

class BookRecsDialog extends Dialog
{
    private string $name = 'bookrecs';
    private int $perPage = 5;

    protected array $steps = [
        'name', // название и автор книги
    ];

    public function nameStep(string $message)
    {
        /** @var ElasticsearchService $searchService */
        $searchService = app(ElasticsearchService::class);
        /** @var ElasticBooks $searchModel */
        $searchModel = app(ElasticBooks::class);

        $query = $searchModel->getSearchQuery($message);
        $result = $searchService->search($query);

        if ($result && !empty($result->hits)) {
            $text = "Вот что мы нашли на наших книжных полках:\n\n";
            foreach ($result->hits as $key => $item) {
                $sourceId = $item->_source['id'];
                $sourceAuthor = $item->_source['author'];
                $sourceTitle = $item->_source['title'];

                $wrKey = $key + 1;
                $text .= "#$wrKey: *{$sourceAuthor} - {$sourceTitle}*\n";
                $text .= "Рекомендации: /recs{$sourceId}\n\n";
                $this->chatInfo->dialog->search[$wrKey] = $sourceId;
            }

            $text .= "Если искомой книги нет в списке, не расстраивайтесь. ";
            $text .= "Вы можете добавить её с помощью команды /addbook и мы подберём для неё рекомендации.";
        } else {
            $text = "К сожалению, такой книги пока нет в нашей библиотеке. ";
            $text .= "Мы автоматически добавим её и скоро она появится в рекомендациях.";
        }

        $this->completeStep();
        $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => 'markdown',
        ]);
    }
}
