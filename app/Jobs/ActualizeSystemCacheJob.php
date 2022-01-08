<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\Exception;
use App\Models\Genre;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ActualizeSystemCacheJob extends QueueJob
{
    public function handle()
    {
        $this->log("Начинается актуализация кеша с системной информацией");

        $data = [
            'books' => [
                'total' => Book::query()->count(),
                'diff' => Book::getDiffCount(),
            ],
            'authors' => [
                'total' => Book::getAuthorsCount(),
                'diff' => Book::getAuthorsDiffCount(),
            ],
            'genres' => [
                'total' => Genre::query()->count(),
                'diff' => Genre::getDiffCount(),
            ],
            'telegramMessages' => [
                'total' => TelegramMessage::query()->count(),
                'diff' => TelegramMessage::getDiffCount(),
                'month_total' => TelegramMessage::countByMonth(),
                'month_diff' => TelegramMessage::diffByMonth(),
                'year_total' => TelegramMessage::countByYear(),
                'year_diff' => TelegramMessage::diffByYear(),
            ],
            'telegramUsers' => [
                'total' => TelegramUser::query()->count(),
                'diff' => TelegramUser::getDiffCount(),
                'month_total' => TelegramUser::countByMonth(),
                'month_diff' => TelegramUser::diffByMonth(),
                'year_total' => TelegramUser::countByYear(),
                'year_diff' => TelegramUser::diffByYear(),
            ],
            'users' => [
                'total' => User::query()->count(),
                'diff' => User::getDiffCount(),
                'month_total' => User::countByMonth(),
                'month_diff' => User::diffByMonth(),
                'year_total' => User::countByYear(),
                'year_diff' => User::diffByYear(),
            ],
            'exceptions' => [
                'total' => Exception::query()->count(),
                'diff' => Exception::getDiffCount(),
            ],
        ];

        Cache::put('system.info', $data);

        $this->log("Актуализация кеша с системной информацией успешно завершена");
    }
}
