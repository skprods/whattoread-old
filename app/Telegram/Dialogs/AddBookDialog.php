<?php

namespace App\Telegram\Dialogs;

use App\Services\BooksService;
use App\Models\Elasticsearch\ElasticBooks;
use App\Services\ElasticsearchService;
use App\Telegram\TelegramDialog;
use Illuminate\Support\Facades\Log;

class AddBookDialog extends TelegramDialog
{
    public string $name = 'addbook';

    public string $description = 'Добавить прочитанную книгу';

    protected array $steps = [
        'title',
        'author',
        'confirm',
        'rating',
        'associations',
    ];

    public function handle()
    {
        $this->replyWithMessage([
            'text' => "Давайте добавим новую книгу, которую вы прочитали. Для начала, напишите её название"
        ]);
    }

    public function titleStep()
    {
        $message = $this->update->message->text;
        $this->chatInfo->dialog->data['title'] = $message;

        $this->replyWithMessage(['text' => 'Кто автор этой книги?']);
    }

    public function authorStep()
    {
        $author = $this->update->message->text;
        Log::info(json_encode($author, JSON_UNESCAPED_UNICODE));
        $this->chatInfo->dialog->data['author'] = $author;

        /** Название книги мы получили на предыдущем шаге */
        $title = $this->chatInfo->dialog->data['title'];

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
                $this->chatInfo->dialog->data['search'][$wrKey] = $sourceId;
            }

            $text .= "\n";
            $text .= "Если среди них есть нужная, отправьте её номер (без #). Если ни одна из книг не подходит, отправьте на 0";
        } else {
            $text = "К сожалению, такой книги ещё нет в нашей библиотеке. Мы автоматически добавим её. Для продолжения введите 0\n";
        }

        $this->replyWithMessage(['text' => $text]);
    }

    public function confirmStep()
    {
        $message = $this->update->message->text;
        if ((int) $message !== 0) {
            $bookId = $this->chatInfo->dialog->data['search'][$message];
            $this->chatInfo->dialog->data['selectedBookId'] = $bookId;
        }

        $this->replyWithMessage([
            'text' => 'Хорошо. Оцените эту книгу по шкале от 1 (не понравилась) до 5 (понравилась)'
        ]);
    }

    public function ratingStep()
    {
        $message = $this->update->message->text;
        $answer = (int) $message;

        if ($answer < 1) {
            $answer = 1;
        } elseif ($answer > 5) {
            $answer = 5;
        }

        $this->chatInfo->dialog->data['bookRating'] = $answer;

        $text = "[необязательно]\n";
        $text .= "С чем у вас ассоциируется эта книга?\n\n";
        $text .= "Напишите одну или несколько ассоциаций (по одной в сообщении или через запятую). ";
        $text .= "Можно использовать словосочетания или обычные слова. Когда закончите, в отдельном сообщении напишите Конец.\n\n";
        $text .= "Если вы не хотите отвечать, напишите Пропустить";
        $this->replyWithMessage(['text' => $text]);
    }

    public function associationsStep()
    {
        $this->stepCompleted = false;
        $message = $this->update->message->text;

        /** @var BooksService $booksService */
        $booksService = app(BooksService::class);

        if (mb_strtolower($message) === "конец" || mb_strtolower($message) === "пропустить") {
            $this->replyWithMessage(['text' => 'Добавляем книгу...']);
            $booksService->createFromTelegram($this->chatInfo->dialog->data, $this->chatInfo->id);

            $this->replyWithMessage([
                'text' => 'Книга добавлена. Теперь мы сделаем рекомендации ещё более точными!'
            ]);
            $this->stepCompleted = true;
        } else {
            $messages = explode(',', $message);

            foreach ($messages as $message) {
                $this->chatInfo->dialog->data['associations'][] = trim($message);
            }
        }
    }
}
