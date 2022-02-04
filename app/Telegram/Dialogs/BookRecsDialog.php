<?php

namespace App\Telegram\Dialogs;

use App\Managers\KeyboardParamManager;
use App\Models\KeyboardParam;
use App\Services\BooksService;
use App\Telegram\TelegramDialog;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class BookRecsDialog extends TelegramDialog
{
    private KeyboardParamManager $keyboardParamManager;
    private BooksService $booksService;

    public string $name = 'bookrecs';
    public string $description = 'Получить рекомендации на книгу';
    protected array $steps = [
        'name', // название и автор книги
    ];

    private int $perPage = 10;

    public function __construct()
    {
        $this->keyboardParamManager = app(KeyboardParamManager::class);
        $this->booksService = app(BooksService::class);

        parent::__construct();
    }

    public function handle()
    {
        $this->replyWithMessage(['text' => "Для какой книге нужно найти похожие? Напишите название и/или автора."]);
    }

    public function nameStep()
    {
        $this->stepCompleted = false;

        $message = $this->update->message->text;
        $search = $this->booksService->findBookInElastic($message, $this->perPage);
        $books = $search['items'];
        $count = $search['total'];

        $text = $this->getText($books);
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
            'param' => $message,
        ]);
    }

    public function nameCallback()
    {
        $this->stepCompleted = false;

        $callbackData = $this->getCallbackData($this->update->callbackQuery->data);
        $page = (int) $callbackData['data'];
        $updateId = $callbackData['update_id'];

        $keyboardParam = KeyboardParam::findByUpdateId($updateId);
        if (!$keyboardParam) {
            return;
        }

        $search = $this->booksService
            ->findBookInElastic($keyboardParam->param, $this->perPage, $this->perPage * ($page - 1));
        $books = $search['items'];
        $count = $search['total'];

        $text = $this->getText($books, $page);
        $keyboard = $this->getKeyboard($updateId, $count, $this->perPage, $page);

        try {
            if (count($keyboard) < 2) {
                $this->editMessageText([
                    'chat_id' => $this->getChat()->id,
                    'message_id' => $this->update->callbackQuery->message->messageId,
                    'text' => $text,
                    'parse_mode' => 'markdown',
                ]);
            } else {
                $this->editMessageText([
                    'chat_id' => $this->getChat()->id,
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

    private function getText(array $books, int $page = 1): string
    {
        $keySum = $this->perPage * ($page - 1);

        if (!empty($books)) {
            $text = "Вот что мы нашли на наших книжных полках.\n\n";
            $text .= "Если в списке есть искомая книга, нажмите на команду рекомендаций под её описанием, ";
            $text .= "чтобы получить подборку на выбранную книгу:\n\n";
            $text .= "Если искомой книги нет в списке, не расстраивайтесь. ";
            $text .= "Вы можете добавить её с помощью команды /addbook и мы подберём для неё рекомендации.\n\n";

            foreach ($books as $key => $item) {
                $sourceId = $item->_source['id'];
                $sourceAuthor = $item->_source['author'];
                $sourceTitle = $item->_source['title'];

                $wrKey = $key + 1 + $keySum;
                $text .= "#$wrKey: *{$sourceAuthor} - {$sourceTitle}*\n";
                $text .= "Рекомендации: /recs{$sourceId}\n\n";
                $this->chatInfo->dialog->data['search'][$wrKey] = $sourceId;
            }
        } else {
            $text = "К сожалению, такой книги пока нет в нашей библиотеке. ";
            $text .= "Мы автоматически добавим её и скоро она появится в рекомендациях.";
        }

        return $text;
    }
}
