<?php

namespace App\Telegram\Dialogs;

use App\Managers\KeyboardParamManager;
use App\Managers\RecommendationListManager;
use App\Models\Book;
use App\Models\BookRecs;
use App\Models\BookRecsShort;
use App\Models\KeyboardParam;
use App\Models\TelegramUserBook;
use App\Telegram\TelegramDialog;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
    private RecommendationListManager $recommendationListManager;

    public function __construct()
    {
        $this->keyboardParamManager = app(KeyboardParamManager::class);
        $this->recommendationListManager = app(RecommendationListManager::class);

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
        $bookId = $this->chatInfo->dialog->data['bookId'];
        $book = Book::findOrFail($bookId);

        $this->replyWithMessage([
            'text' => "Готовим рекомендации...",
            'reply_markup' => json_encode([
                'remove_keyboard' => true,
            ]),
        ]);

        $shortRecs = BookRecsShort::find($bookId);
        $recs = $this->getRecs($shortRecs, $withAuthor);

        $count = $recs->count();
        if ($count === 0) {
            $text = "К сожалению, пока что у нас нет рекомендаций к книге {$book->author} - {$book->title}, ";
            $text .= "но скоро они появятся. Попробуйте позже.";
            $this->replyWithMessage(['text' => $text]);

            return;
        }

        $booksMessage = self::getBooksMessage($count);
        $text = "С книгой {$book->author} - {$book->title} мы рекомендуем {$booksMessage}: \n\n";

        $recs = $recs->chunk($this->perPage);
        $text = $this->getMessage($text, $recs->get(0));

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

        try {
            $recs = $this->getRecs($shortRecs, $withAuthor);
            $this->recommendationListManager
                ->saveRecommendations($recs, $bookId, $this->update->updateId, $this->chatInfo->id, true);

            /** Ждём две секунды, чтобы просьба об оценке не слилась со списком и пришла отдельно, а не одновременно */
            sleep(2);
            $this->replyWithMessage([
                'text' => "Пожалуйста, оцените эту подборку",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        $this->getRatingKeyboard($this->update->updateId),
                    ]
                ])
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
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
        $withAuthor = $withAuthor === '1';

        /** @var Book $book */
        $book = Book::findOrFail($bookId);

        if ($pageNumber <= 0) {
            return;
        }

        $shortRecs = BookRecsShort::find($bookId);
        $recs = $this->getRecs($shortRecs, $withAuthor);

        $count = $recs->count();
        $booksMessage = self::getBooksMessage($count);
        $text = "С книгой {$book->author} - {$book->title} мы рекомендуем {$booksMessage}: \n\n";

        $recs = $recs->chunk($this->perPage);
        $rec = $this->getRec($recs, $pageNumber - 1);
        if (!$rec) {
            $text = "К сожалению, пока что у нас нет рекомендаций к книге {$book->author} - {$book->title}, ";
            $text .= "но скоро они появятся. Попробуйте позже.";
            $this->editMessageText([
                'chat_id' => $this->chatInfo->id,
                'message_id' => $this->update->callbackQuery->message->messageId,
                'text' => $text,
                'parse_mode' => 'markdown',
            ]);

            return;
        }

        $text = $this->getMessage($text, $rec);

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

        /** Дополняем просмотренные рекомендации в recommendation_lists */
        try {
            $recs = $this->getRecs($shortRecs, $withAuthor);
            $this->recommendationListManager
                ->saveRecommendations($recs, $bookId, $updateId, $this->chatInfo->id, false);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function getRecs(BookRecsShort $shortRecs, bool $withAuthor): Collection
    {
        $excludedBookIds = TelegramUserBook::getUserBookIds($this->telegramUser->id);
        unset($excludedBookIds[$shortRecs->book_id]);

        $recs = collect($shortRecs->data);
        if ($withAuthor === false) {
            $recs = $recs->filter(function ($rec) {
                return $rec['author_score'] === 0;
            });
        }

        /** Исклчаем уже добавленные пользователем книги */
        $recs = $recs->filter(function ($rec) use ($excludedBookIds) {
            return !isset($excludedBookIds[$rec['book_id']]);
        });

        return $recs->sortByDesc('total_score');
    }

    private function getBuilder(int $bookId, bool $withAuthor): Builder
    {
        /** Исключаем книги, которые уже прочитал (добавил) пользователь, за исключением $bookId, по которой ищут */
        $excludedBookIds = TelegramUserBook::getUserBookIds($this->telegramUser->id);
        unset($excludedBookIds[$bookId]);

        $builder = BookRecs::query()
            ->orWhere('comparing_book_id', $bookId)
            ->orWhere('matching_book_id', $bookId)
            ->whereNotIn('comparing_book_id', $excludedBookIds)
            ->whereNotIn('comparing_book_id', $excludedBookIds)
            ->orderByDesc('total_score');

        if ($withAuthor === false) {
            $builder = BookRecs::query()->from($builder);
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

    private function getMessage(string $text, Collection $recs): string
    {
        $bookIds = $recs->keys()->toArray();
        $books = Book::whereIdIn($bookIds)
            ->mapWithKeys(function (Book $book) {
                return [$book->id => $book];
            });

        $recs->each(function ($rec) use (&$text, $books) {
            /** @var Book $book */
            $book = $books->get($rec['book_id']);

            $score = round($rec['total_score'] / $this->totalScore * 100, 2);
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

    private function getRec(Collection $recs, int $page)
    {
        if (!$recs->has($page)) {
            do {
                $page -= 1;

                if ($recs->has($page)) {
                    return $recs->get($page);
                }
            } while ($page >= 0);
        }

        return null;
    }

    private function getRatingKeyboard(int $updateId): array
    {
        return [
            ['text' => 1, 'callback_data' => "recsRating_rating-1_{$updateId}"],
            ['text' => 2, 'callback_data' => "recsRating_rating-2_{$updateId}"],
            ['text' => 3, 'callback_data' => "recsRating_rating-3_{$updateId}"],
            ['text' => 4, 'callback_data' => "recsRating_rating-4_{$updateId}"],
            ['text' => 5, 'callback_data' => "recsRating_rating-5_{$updateId}"],
        ];
    }

    public static function getCommandNameForBook(int $bookId): string
    {
        $pattern = (new self)->pattern;

        return str_replace("{id}", $bookId, $pattern);
    }
}
