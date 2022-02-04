<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUserBook;
use App\Telegram\TelegramCommand;
use App\Traits\HasDeclination;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class MyBooksCommand extends TelegramCommand
{
    use HasDeclination;

    public string $name = 'mybooks';
    public string $description = 'Добавленные книги';

    private int $perPage = 5;

    protected function handle()
    {
        $builder = $this->telegramUser->books()->orderByDesc('id');

        $count = $builder->count();
        $books = $builder->limit($this->perPage)->get();

        $booksMessage = $this->getBooksDeclination($count);
        $text = "Вы добавили {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $books);

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
        $callbackQuery = $this->update->callbackQuery;
        $pageNumber = (int) $this->chatInfo->currentCommand->callbackData;

        if ($pageNumber <= 0) {
            return;
        }

        $builder = $this->telegramUser->books()->orderByDesc('id');

        $count = $builder->count();
        $books = $builder->limit($this->perPage)->offset($this->perPage * ($pageNumber - 1))->get();

        $booksMessage = $this->getBooksDeclination($count);
        $text = "Вы добавили {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $books);

        $keyboard = $this->getKeyboard($this->update->updateId, $count, $this->perPage, $pageNumber);

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
        } catch (ClientException $exception) {
            Log::error($exception->getMessage());
        }
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
}
