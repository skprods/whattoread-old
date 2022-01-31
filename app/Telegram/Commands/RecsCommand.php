<?php

namespace App\Telegram\Commands;

use App\Models\Book;
use App\Models\BookMatching;
use App\Models\TelegramUserBook;
use App\Telegram\TelegramCommand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class RecsCommand extends TelegramCommand
{
    public bool $show = false;

    public string $name = 'recs';
    public string $pattern = "recs{id}";
    public string $description = 'Рекомендации для книги';

    private int $perPage = 5;

    protected function handle()
    {
        $bookId = $this->arguments['id'];
        $book = Book::findOrFail($bookId);

        $builder = $this->getBuilder($bookId);

        $count = $builder->count();
        if (!$count) {
            $text = "У нас нет рекомендаций к книге {$book->author} - {$book->title}, ";
            $text .= "но скоро они появятся. Попробуйте позже.";
            $this->replyWithMessage(['text' => $text]);

            return;
        }

        $bookMatches = $builder->limit($this->perPage)->get();

        $booksMessage = self::getBooksMessage($count);
        $text = "С книгой {$book->author} - {$book->title} мы рекомендуем {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $bookMatches, $bookId);

        $keyboard = $this->getKeyboard($this->update->updateId, $count, $this->perPage);

        if (count($keyboard) < 2) {
            $this->replyWithMessage([
                'text' => $text,
                'parse_mode' => 'markdown',
            ]);
        } else {
            $this->replyWithMessage([
                'text' => $text,
                'parse_mode' => 'markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        $keyboard,
                    ]
                ])
            ]);
        }
    }

    protected function handleCallback()
    {
        $pageNumber = (int) $this->chatInfo->currentCommand->callbackData;
        $bookId = $this->chatInfo->previousCommand->arguments['id'];

        /** @var Book $book */
        $book = Book::findOrFail($bookId);

        if ($pageNumber <= 0) {
            return;
        }

        $builder = $this->getBuilder($bookId);

        $count = $builder->count();
        $bookMatches = $builder->limit($this->perPage)->offset($this->perPage * ($pageNumber - 1))->get();

        $booksMessage = self::getBooksMessage($count);
        $text = "С книгой {$book->author} - {$book->title} мы рекомендуем {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $bookMatches, $bookId);

        $keyboard = $this->getKeyboard($this->update->updateId, $count, $this->perPage, $pageNumber);

        if (count($keyboard) < 2) {
            $this->editMessageText([
                'chat_id' => $this->chatInfo->id,
                'message_id' => $this->update->callbackQuery->message->messageId,
                'text' => $text,
                'parse_mode' => 'markdown',
            ]);
        } else {
            $this->editMessageText([
                'chat_id' => $this->chatInfo->id,
                'message_id' => $this->update->callbackQuery->message->messageId,
                'text' => $text,
                'parse_mode' => 'markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        $keyboard,
                    ]
                ])
            ]);
        }
    }

    private function getBuilder(int $bookId): Builder
    {
        /** Исключаем книги, которые уже прочитал (добавил) пользователь, за исключением $bookId, по которой ищут */
        $excludedBookIds = TelegramUserBook::getUserBookIds($this->telegramUser->id);
        unset($excludedBookIds[$bookId]);

        return BookMatching::query()
            ->where('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->whereNotIn('comparing_book_id', $excludedBookIds)
            ->whereNotIn('comparing_book_id', $excludedBookIds)
            ->orderByDesc('total_score');
    }

    private static function getBooksMessage(int $count): string
    {
        if ($count >= 11 && $count < 20) {
            return "$count книг";
        }

        return match ($count % 10) {
            1 => "$count книгу",
            2, 3, 4 => "$count книги",
            5, 6, 7, 8, 9, 0 => "$count книг",
            default => "",
        };
    }

    private function getMessage(string $text, Collection $bookMatches, int $searchBookId): string
    {
        $bookMatches->each(function (BookMatching $bookMatching) use (&$text, $searchBookId) {
            if ($bookMatching->comparing_book_id === $searchBookId) {
                $book = $bookMatching->matchingBook;
            } else {
                $book = $bookMatching->comparingBook;
            }

            $score = round($bookMatching->total_score / 200 * 100, 2);
            $description = mb_strlen($book->description) > 300
                ? mb_substr($book->description, 0, 300) . "..."
                : $book->description;

            $text .= "*{$book->title}*\n";
            $text .= "{$book->author}\n";
            $text .= "Совпадение: *{$score}%*\n";
            $text .= "Подробнее: /book{$book->id}\n\n";

            $text .= "$description\n\n\n";
        });

        return $text;
    }

    public static function getCommandNameForBook(int $bookId): string
    {
        $pattern = (new self)->pattern;

        return str_replace("{id}", $bookId, $pattern);
    }
}
