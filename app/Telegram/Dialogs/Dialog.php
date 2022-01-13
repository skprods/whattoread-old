<?php

namespace App\Telegram\Dialogs;

use App\Entities\ChatInfo;
use App\Managers\TelegramMessageManager;
use App\Models\TelegramUser;
use App\Services\RedisService;
use App\Telegram\Telegram;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Message;

abstract class Dialog
{
    protected bool $endOfStep = false;
    protected ChatInfo $chatInfo;
    protected Message $message;

    /** Шаги, готовые нужно пройти (по-порядку) */
    protected array $steps = [];

    protected ?string $currentStep = null;
    protected array $responses = [];

    protected TelegramMessageManager $telegramMessageManager;
    protected Telegram $telegram;
    protected RedisService $redisService;

    public function __construct(Telegram $telegram, ChatInfo $chatInfo, Message $message)
    {
        $this->telegramMessageManager = app(TelegramMessageManager::class);
        $this->telegram = $telegram;
        $this->chatInfo = $chatInfo;
        $this->message = $message;

        $this->redisService = app(RedisService::class);
    }

    public function handle()
    {
        $completedSteps = $this->chatInfo->dialog->completedSteps;

        if (empty($completedSteps)) {
            $this->currentStep = $this->steps[0];
        } else {
            $lastStep = end($completedSteps);

            foreach ($this->steps as $key => $step) {
                if ($step === $lastStep) {
                    $this->currentStep = $this->steps[$key + 1];
                }
            }
        }

        $message = htmlspecialchars(trim($this->message->text));
        $this->logMessage($message);

        $this->{$this->currentStep . "Step"}($message);

        $this->logResponses($this->responses);
        $this->redisService->setChatInfo($this->chatInfo);
    }

    /**
     * @throws TelegramSDKException
     */
    protected function replyWithMessage(array $response)
    {
        $data = array_merge($response, ['chat_id' => $this->chatInfo->id]);

        $this->addResponse($data);
        $this->telegram->sendMessage($data);
    }

    protected function completeStep(string $step = null)
    {
        $step = $step ?? $this->currentStep;

        $this->chatInfo->dialog->completedSteps[] = $step;
    }

    public function __destruct()
    {
        $lastKey = array_key_last($this->steps);
        $lastStep = $this->steps[$lastKey];

        if ($this->currentStep === $lastStep && $this->endOfStep) {
            $this->resetChatInfo();
        }
    }

    protected function resetChatInfo()
    {
        $chatInfo = new ChatInfo($this->chatInfo->id);
        $this->redisService->setChatInfo($chatInfo);
    }

    protected function addResponse(array $response)
    {
        $this->responses[] = $response;
    }

    private function logMessage(string $message)
    {
        $user = TelegramUser::findByTelegramId($this->chatInfo->id);

        $this->telegramMessageManager->create([
            'command' => $this->chatInfo->lastCommand->command,
            'message' => $message
        ], $user);
    }

    public function logResponses(array $responses)
    {
        $this->telegramMessageManager->update(['responses' => $responses]);
    }
}
