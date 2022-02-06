<?php

namespace App\Telegram\Dialogs;

use App\Managers\KeyboardParamManager;
use App\Models\Book;
use App\Models\BookMatching;
use App\Models\KeyboardParam;
use App\Models\TelegramUserBook;
use App\Telegram\TelegramDialog;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class RecsDialog extends TelegramDialog
{
    public bool $show = false;

    public string $name = 'recs';
    public string $pattern = "recs{id}";
    public string $description = 'Рекомендации для книги';
    protected array $steps = [
        'withAuthor',
    ];

    private int $perPage = 5;
    private int $totalScore = 180;

    private KeyboardParamManager $keyboardParamManager;

    public function __construct()
    {
        $this->keyboardParamManager = app(KeyboardParamManager::class);

        parent::__construct();
    }

    protected function getCurrentStep()
    {
        $currentStep = parent::getCurrentStep();

        if (!$currentStep && $this->update->callbackQuery) {
            return 'withAuthor';
        }

        return $currentStep;
    }

    public function handle()
    {
        $this->chatInfo->dialog->data['bookId'] = $this->arguments['id'];

        $this->replyWithMessage([
            'text' => "Хотите ли вы видеть в подборке книги этого же автора?",
            'reply_markup' => json_encode([
                'keyboard' => [["Да", "Нет"]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]),
        ]);
    }

    protected function withAuthorStep()
    {
        $withAuthor = $this->update->message->text === "Да";
        Log::info((int) $withAuthor);
        $bookId = $this->chatInfo->dialog->data['bookId'];
        $book = Book::findOrFail($bookId);

        $this->replyWithMessage([
            'text' => "Готовим рекомендации...",
        ]);
        $this->replyWithChatAction([
            'action' => 'typing',
        ]);

        $builder = $this->getBuilder($bookId, $withAuthor);

        $count = $builder->count();
        if (!$count) {
            $text = "К сожалению, пока что у нас нет рекомендаций к книге {$book->author} - {$book->title}, ";
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

        $this->keyboardParamManager->create([
            'update_id' => $this->update->updateId,
            'param' => "{$bookId}_" . (int) $withAuthor,
        ]);
    }

    protected function withAuthorCallback()
    {
        $callbackData = $this->getCallbackData($this->update->callbackQuery->data);
        $pageNumber = (int) $callbackData['data'];
        $updateId = $callbackData['update_id'];

        $keyboardParam = KeyboardParam::findByUpdateId($updateId);
        if (!$keyboardParam) {
            return;
        }
        [$bookId, $withAuthor] = explode('_', $keyboardParam->param);

        $this->replyWithChatAction([
            'action' => 'typing',
        ]);

        /** @var Book $book */
        $book = Book::findOrFail($bookId);

        if ($pageNumber <= 0) {
            return;
        }

        $builder = $this->getBuilder($bookId, (bool) $withAuthor);

        $count = $builder->count();
        $bookMatches = $builder->limit($this->perPage)->offset($this->perPage * ($pageNumber - 1))->get();

        $booksMessage = self::getBooksMessage($count);
        $text = "С книгой {$book->author} - {$book->title} мы рекомендуем {$booksMessage}: \n\n";
        $text = $this->getMessage($text, $bookMatches, $bookId);

        $keyboard = $this->getKeyboard($updateId, $count, $this->perPage, $pageNumber);

        try {
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
        } catch (ClientException $exception) {
            Log::error($exception->getMessage());
        }
    }

    private function getBuilder(int $bookId, bool $withAuthor): Builder
    {
        /** Исключаем книги, которые уже прочитал (добавил) пользователь, за исключением $bookId, по которой ищут */
        $excludedBookIds = TelegramUserBook::getUserBookIds($this->telegramUser->id);
        unset($excludedBookIds[$bookId]);

        $builder = BookMatching::query()
            ->orWhere('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->whereNotIn('comparing_book_id', $excludedBookIds)
            ->whereNotIn('comparing_book_id', $excludedBookIds)
            ->orderByDesc('total_score');

        if ($withAuthor === false) {
            $builder = BookMatching::query()->from($builder);
            $builder->where('author_score', '=', 0);
        }

        return $builder;
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

            $score = round($bookMatching->total_score / $this->totalScore * 100, 2);
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
