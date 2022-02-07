<?php

namespace App\Managers;

use App\Models\RecommendationList;
use App\Models\TelegramUser;
use Illuminate\Support\Collection;

class RecommendationListManager
{
    /** Сохранить/дополнить рекомендации */
    public function saveRecommendations(
        Collection $recs,
        int $bookId,
        int $updateId,
        int $telegramId,
        bool $create = true
    ): ?RecommendationList {
        $bookIds = $recs->mapWithKeys(function ($rec) use ($bookId) {
            return [$rec['book_id'] => $rec['total_score']];
        })->toArray();

        $telegramUser = TelegramUser::findByTelegramIdOrFail($telegramId);

        if ($create) {
            return $this->create($updateId, $telegramUser->id, $bookId, $bookIds);
        } else {
            return $this->addBooksToList($updateId, $telegramUser->id, $bookIds);
        }
    }

    private function create(int $updateId, int $telegramUserId, int $bookId, array $bookIds): RecommendationList
    {
        /** @var RecommendationList $list */
        $list = app(RecommendationList::class);
        $list->fill([
            'update_id' => $updateId,
            'recommendations' => $bookIds,
        ]);
        $list->telegramUser()->associate($telegramUserId);
        $list->book()->associate($bookId);
        $list->save();

        return $list;
    }

    private function addBooksToList(int $updateId, int $telegramUserId, array $bookIds): ?RecommendationList
    {
        $list = RecommendationList::findByUpdateIdAndTelegramId($updateId, $telegramUserId);

        if (!$list) {
            return null;
        }

        $list->recommendations = $list->recommendations + $bookIds;
        $list->save();

        return $list;
    }

    public function setRating(int $updateId, int $telegramId, int $rating): ?RecommendationList
    {
        $telegramUser = TelegramUser::findByTelegramIdOrFail($telegramId);
        $list = RecommendationList::findByUpdateIdAndTelegramId($updateId, $telegramUser->id);

        if (!$list) {
            return null;
        }

        $list->rating = $rating;
        $list->save();

        return $list;
    }
}
