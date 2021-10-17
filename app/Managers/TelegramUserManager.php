<?php

namespace App\Managers;

use App\Models\TelegramUser;

class TelegramUserManager
{
    protected ?TelegramUser $telegramUser;

    public function __construct(TelegramUser $telegramUser = null)
    {
        $this->telegramUser = $telegramUser;
    }

    public function createOrUpdate(array $params): TelegramUser
    {
        $this->telegramUser = TelegramUser::getUserByTelegramId($params['telegram_id']);

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

        return $this->telegramUser;
    }

    public function update(array $params): TelegramUser
    {
        $this->telegramUser->fill($params);
        $this->telegramUser->save();

        return $this->telegramUser;
    }
}
