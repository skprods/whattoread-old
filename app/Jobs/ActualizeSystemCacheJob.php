<?php

namespace App\Jobs;

use App\Models\Book;
use App\Models\Genre;
use App\Models\TelegramMessage;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ActualizeSystemCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
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
            ],
            'telegramUsers' => [
                'total' => TelegramUser::query()->count(),
                'diff' => TelegramUser::getDiffCount(),
            ],
            'users' => [
                'total' => User::query()->count(),
                'diff' => User::getDiffCount(),
            ],
        ];

        Cache::put('system.info', $data);
    }
}
