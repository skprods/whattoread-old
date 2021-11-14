<?php

namespace App\Telegram\Dialogs;

use App\Entities\ChatInfo;
use App\Managers\TelegramMessageManager;
use App\Models\TelegramUser;
use App\Services\RedisService;
use Telegram\Bot\Api;
use Telegram\Bot\BotsManager;
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

    protected BotsManager $botsManager;
    protected Api $telegram;
    protected RedisService $redisService;

    public function __construct(BotsManager $telegram, ChatInfo $chatInfo, Message $message)
    {
        $this->botsManager = $telegram;
        $this->telegram = $telegram->bot();
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
        $this->{$this->currentStep . "Step"}($message);

        $this->logMessage($message, $this->responses);
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

    private function logMessage(string $message, array $responses)
    {
        $user = TelegramUser::getUserByTelegramId($this->chatInfo->id);

        app(TelegramMessageManager::class)->create([
            'command' => $this->chatInfo->lastCommand,
            'message' => $message,
            'responses' => $responses
        ], $user);
    }
}
