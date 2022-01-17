<?php

namespace App\Telegram\Dialogs;

use App\Managers\BooksManager;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\DB;

class AddBookDialog extends Dialog
{
    protected array $steps = [
        'title',
        'author',
        'confirm',
        'rating',
        'associations',
    ];

    public function titleStep(string $message)
    {
        $this->chatInfo->dialog->messages[$this->currentStep][] = $message;
        $this->completeStep();

        $this->replyWithMessage(['text' => 'Кто автор этой книги?']);
    }

    public function authorStep(string $message)
    {
        $this->chatInfo->dialog->messages[$this->currentStep][] = $message;
        $this->completeStep();

        /** Название книги мы получили на предыдущем шаге */
        $title = $this->chatInfo->dialog->messages['title'][0];

        /** Автора нам прислали только что */
        $author = $message;

        /** @var ElasticsearchService $searchService */
        $searchService = app(ElasticsearchService::class);
        /** @var ElasticBooks $searchModel */
        $searchModel = app(ElasticBooks::class);

        $query = $searchModel->getTitleAuthorQuery($title, $author);
        $result = $searchService->search($query);

        if ($result && !empty($result->hits)) {
            $text = "Вот что мы нашли:\n";
            foreach ($result->hits as $key => $item) {
                $sourceId = $item->_source['id'];
                $sourceAuthor = $item->_source['author'];
                $sourceTitle = $item->_source['title'];

                $wrKey = $key + 1;
                $text .= "#$wrKey: {$sourceAuthor} - {$sourceTitle}\n";
                $this->chatInfo->dialog->search[$wrKey] = $sourceId;
            }

            $text .= "\n";
            $text .= "Если среди них есть нужная, отправьте её номер (без #). Если ни одна из книг не подходит, отправьте на 0";
        } else {
            $text = "К сожалению, такой книги ещё нет в нашей библиотеке. Мы автоматически добавим её. Для продолжения введите 0\n";
        }

        $this->replyWithMessage(['text' => $text]);
    }

    public function confirmStep(string $message)
    {
        if ((int) $message !== 0) {
            $bookId = $this->chatInfo->dialog->search[$message];
            $this->chatInfo->dialog->selectedBookId = $bookId;
        }

        $this->completeStep();
        $this->replyWithMessage(['text' => 'Хорошо. Оцените эту книгу по шкале от 1 (не понравилась) до 5 (понравилась)']);
    }

    public function ratingStep(string $message)
    {
        $answer = (int) $message;

        if ($answer < 1) {
            $answer = 1;
        } elseif ($answer > 5) {
            $answer = 5;
        }

        $this->chatInfo->dialog->bookRating = $answer;
        $this->completeStep();

        $text = "[необязательно]\n";
        $text .= "С чем у вас ассоциируется эта книга?\n\n";
        $text .= "Напишите одну или несколько ассоциаций (по одной в сообщении или через запятую). ";
        $text .= "Можно использовать словосочетания или обычные слова. Когда закончите, в отдельном сообщении напишите Конец.\n\n";
        $text .= "Если вы не хотите отвечать, напишите Пропустить";
        $this->replyWithMessage(['text' => $text]);
    }

    public function associationsStep(string $message)
    {
        if (
            mb_strtolower($message) === "конец"
            || mb_strtolower($message) === "пропустить"
        ) {
            $this->completeStep();

            $this->replyWithMessage(['text' => 'Добавляем книгу...']);
            try {
                app(BooksManager::class)->addFromTelegram($this->chatInfo);
                $this->replyWithMessage(['text' => 'Книга добавлена. Теперь мы сделаем рекомендации ещё более точными!']);
                $this->endOfStep = true;
            } catch (\Exception $exception) {
                $this->resetChatInfo();
                throw $exception;
            }
        } else {
            $messages = explode(',', $this->message->text);

            foreach ($messages as $message) {
                $this->chatInfo->dialog->messages[$this->currentStep][] = trim($message);
            }
        }
    }
}
