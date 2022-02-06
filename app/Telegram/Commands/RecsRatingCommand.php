<?php

namespace App\Telegram\Commands;

use App\Managers\RecommendationListManager;
use App\Telegram\TelegramCommand;

class RecsRatingCommand extends TelegramCommand
{
    public bool $show = false;
    public string $name = 'recsRating';
    public string $description = 'Выставление рейтинга подборке';

    private RecommendationListManager $recommendationListManager;

    public function __construct()
    {
        parent::__construct();

        $this->recommendationListManager = app(RecommendationListManager::class);
    }

    protected function handle()
    {
    }

    protected function handleCallback()
    {
        $callbackData = $this->getCallbackData($this->update->callbackQuery->data);

        if (str_contains($callbackData['data'], 'rating-')) {
            $updateId = $callbackData['update_id'];
            [$prefix, $rating] = explode('-', $callbackData['data']);
            $this->recommendationListManager->setRating((int) $updateId, $this->chatInfo->id, (int) $rating);

            $this->editMessageText([
                'chat_id' => $this->chatInfo->id,
                'message_id' => $this->update->callbackQuery->message->messageId,
                'text' => "Пожалуйста, оцените эту подборку",
                'parse_mode' => 'markdown',
            ]);

            $this->replyWithMessage([
                'text' => "Спасибо за оценку, вы помогаете нам стать лучше!",
            ]);
        }
    }
}
