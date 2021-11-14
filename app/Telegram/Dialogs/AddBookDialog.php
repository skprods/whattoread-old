<?php

namespace App\Telegram\Dialogs;

use App\Managers\BooksManager;
use Illuminate\Support\Facades\DB;

class AddBookDialog extends Dialog
{
    protected array $steps = [
        'title',
        'author',
        'confirm',
        'rating',
        'genres',
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
        $whereTitle = $this->getWhereString('title', explode(' ', $title));

        /** Автора нам прислали только что */
        $author = $message;
        $whereAuthor = $this->getWhereString('author', explode(' ', $author));

        $result = DB::select(DB::raw('
            select s.* from (
                select id, title, author from books where ' . $whereTitle . ' and status = "active"
                union all
                select id, title, author from books where ' . $whereAuthor . ' and status = "active"
            ) as s group by s.id, s.title, s.author limit 9
        '));

        $text = "Вот что мы нашли:\n";
        foreach ($result as $key => $item) {
            $wrKey = $key + 1;
            $text .= "#$wrKey: {$item->author} - {$item->title}\n";
            $this->chatInfo->dialog->search[$wrKey] = $item->id;
        }

        $text .= "\n";
        $text .= "Если среди них есть нужная, отправьте её номер (без #). Если ни одна из книг не подходит, отправьте на 0";

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

        $text = "У нас есть ещё пара вопросов. Отвечать на них необязательно, но ваши ответы помогут нам давать более персонализированные рекомендации.\n\n";
        $text .= "Если вы не хотите отвечать, напишите Пропустить";
        $this->replyWithMessage(['text' => $text]);

        sleep(1);

        $text = "[необязательно]\n";
        $text .= "Какие жанры охватывает эта книга на ваш взгляд? Напишите один или несколько (по одному в сообщении или через запятую).\n";
        $text .= "Например, классика, фэнтези.\n\n";
        $text .= "Как закончите, напишите Конец";
        $this->replyWithMessage(['text' => $text]);
    }

    public function genresStep(string $message)
    {
        if (
            mb_strtolower($message) === "конец"
            || mb_strtolower($message) === "пропустить"
        ) {
            $this->completeStep();
            $this->endOfStep = true;

            $text = "[необязательно]\n";
            $text .= "С чем у вас ассоциируется эта книга?\n\n";
            $text .= "Можно использовать словосочетания или обычные слова. Когда закончите, напишите Конец";
            $this->replyWithMessage(['text' => $text]);
        } else {
            $messages = explode(',', $this->message->text);

            foreach ($messages as $message) {
                $this->chatInfo->dialog->messages[$this->currentStep][] = trim($message);
            }
        }
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
                $this->replyWithMessage(['text' => 'У нас что-то сломалось, поэтому мы не смогли добавить книгу. Приносим свои извиниения']);
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

    private function getWhereString(string $field, array $values): string
    {
        $where = [];
        foreach ($values as $value) {
            $where[] = $field . ' like "%' . $value . '%"';
        }

        return implode(' and ', $where);
    }
}
