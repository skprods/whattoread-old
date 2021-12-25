<?php

namespace App\Managers;

use App\Models\Stat;
use App\Models\TelegramUser;

class TelegramUserManager
{
    protected ?TelegramUser $telegramUser;
    private StatManager $statManager;

    public function __construct(TelegramUser $telegramUser = null)
    {
        $this->telegramUser = $telegramUser;
        $this->statManager = app(StatManager::class);
    }

    public function createOrUpdate(array $params): TelegramUser
    {
        $this->telegramUser = TelegramUser::findByTelegramId($params['telegram_id']);

        if ($this->telegramUser) {
            return $this->update($params);
        } else {
            return $this->create($params);
        }
    }

    public function create(array $params): TelegramUser
    {
        $this->telegramUser = app(TelegramUser::class);
        $this->telegramUser->fill($params);
        $this->telegramUser->save();

        $this->statManager->create(Stat::TELEGRAM_USER_MODEL, $this->telegramUser->id, Stat::CREATED_ACTION);

        return $this->telegramUser;
    }

    public function update(array $params): TelegramUser
    {
        $this->telegramUser->fill($params);
        $this->telegramUser->save();

        return $this->telegramUser;
    }
}
