<?php

namespace App\Telegram\Commands;

use App\Models\Book;
use App\Models\BookMatching;
use Illuminate\Database\Eloquent\Collection;
use Telegram\Bot\Objects\CallbackQuery;

class RecsCommand extends TelegramCommand
{
    public bool $hidden = true;

    protected $name = 'recs{id}';

    protected $description = 'Рекомендации для книги';

    private int $perPage = 5;

    public function handleCommand()
    {
        $bookId = $this->arguments['id'];
        $book = Book::findOrFail($bookId);

        $builder = BookMatching::query()
            ->where('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->orderByDesc('total_score');

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

        $keyboard = $this->getKeyboard($count);

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

    public function handleCallback(CallbackQuery $callbackQuery)
    {
        $pageNumber = (int) $this->getDataFromCallbackQuery($callbackQuery);
        $bookId = $this->chatInfo->lastCommand->param;

        /** @var Book $book */
        $book = Book::findOrFail($bookId);

        if ($pageNumber <= 0) {
            return;
        }

        $builder = BookMatching::query()
            ->where('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->orderByDesc('total_score');

        $count = $builder->count();
        $bookMatches = $builder->limit($this->perPage)->offset($this->perPage * ($pageNumber - 1))->get();

        $booksMessage = self::getBooksMessage($count);
        $text = "С книгой {$book->author} - {$book->title} мы рекомендуем {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $bookMatches, $bookId);

        $keyboard = $this->getKeyboard($count, $pageNumber);

        if (count($keyboard) < 2) {
            $this->editMessageText([
                'chat_id' => $this->chatInfo->id,
                'message_id' => $callbackQuery->message->messageId,
                'text' => $text,
                'parse_mode' => 'markdown',
            ]);
        } else {
            $this->editMessageText([
                'chat_id' => $this->chatInfo->id,
                'message_id' => $callbackQuery->message->messageId,
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
    private static function getBooksMessage(int $count): string
    {
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
            $description = mb_strlen($book->description) > 300 ? mb_substr($book->description, 0, 300) . "..." : $book->description;

            $text .= "*{$book->title}*\n";
            $text .= "{$book->author}\n";
            $text .= "Совпадение: *{$score}%*\n";
            $text .= "/book{$book->id}\n\n";

            $text .= "$description\n\n";
        });

        return $text;
    }

    private function getKeyboard(int $count, int $currentPage = 1): array
    {
        $keyboard = [];
        $pages = ceil($count / $this->perPage);

        for ($page = 1; $page <= $pages; $page++) {
            $pageText = $page === $currentPage ? " • $page • " : $page;

            $keyboard[] = ['text' => $pageText, 'callback_data' => "{$this->name}_{$page}"];
        }

        return $keyboard;
    }
}
