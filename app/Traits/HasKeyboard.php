<?php

namespace App\Traits;

trait HasKeyboard
{
    public function getKeyboard(int $updateId, int $count, int $perPage, int $currentPage = 1): array
    {
        $keyboard = [];
        $pages = ceil($count / $perPage);

        for ($page = 1; $page <= $pages; $page++) {
            $pageText = $page === $currentPage ? " • $page • " : $page;

            $keyboard[] = ['text' => $pageText, 'callback_data' => "{$this->name}_{$page}_{$updateId}"];
        }

        return $keyboard;
    }
}