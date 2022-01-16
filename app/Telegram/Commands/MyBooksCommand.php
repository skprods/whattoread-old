<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUserBook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Objects\CallbackQuery;

class MyBooksCommand extends TelegramCommand
{
    protected $name = 'mybooks';

    protected $description = 'Добавленные книги';

    private int $perPage = 5;

    public function handleCommand()
    {
        $builder = $this->telegramUser->books()->orderByDesc('id');

        $count = $builder->count();
        $books = $builder->limit($this->perPage)->get();

        $booksMessage = self::getBooksMessage($count);
        $text = "Вы добавили {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $books);

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

        if ($pageNumber <= 0) {
            return;
        }

        $builder = $this->telegramUser->books()->orderByDesc('id');

        $count = $builder->count();
        $books = $builder->limit($this->perPage)->offset($this->perPage * ($pageNumber - 1))->get();

        $booksMessage = self::getBooksMessage($count);
        $text = "Вы добавили {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $books);

        $keyboard = $this->getKeyboard($count, $pageNumber);

        try {
            if (count($keyboard) < 2) {
                $this->editMessageText([
                    'chat_id' => $this->getChat()->id,
                    'message_id' => $callbackQuery->message->messageId,
                    'text' => $text,
                    'parse_mode' => 'markdown',
                ]);
            } else {
                $this->editMessageText([
                    'chat_id' => $this->getChat()->id,
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
        } catch (TelegramResponseException $exception) {
            Log::error($exception->getMessage());
        }
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

    private function getMessage(string $text, Collection $books): string
    {
        $books->each(function (TelegramUserBook $telegramUserBook) use (&$text) {
            $text .= "*{$telegramUserBook->book->title}*\n";
            $text .= "{$telegramUserBook->book->author}\n";

            $date = $telegramUserBook->created_at->format("d.m.Y");
            $time = $telegramUserBook->created_at->format("H:i");
            $text .= "Добавлена $date в $time \n\n";
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
