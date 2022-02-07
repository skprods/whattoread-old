<?php

namespace App\Traits;

trait HasKeyboard
{
    public function getKeyboard(int $updateId, int $count, int $perPage, int $currentPage = 1): array
    {
        $keyboard = [];
        $pages = ceil($count / $perPage);

        if ($pages <= 5) {
            for ($page = 1; $page <= $pages; $page++) {
                $pageText = $page === $currentPage ? " • $page • " : $page;

                $keyboard[] = ['text' => $pageText, 'callback_data' => "{$this->name}_{$page}_{$updateId}"];
            }

            return $keyboard;
        }

        if ($currentPage < 4) {
            for ($page = 1; $page <= 3; $page++) {
                $pageText = $page === $currentPage ? " • $page • " : $page;

                $keyboard[] = ['text' => $pageText, 'callback_data' => "{$this->name}_{$page}_{$updateId}"];
            }

            $keyboard[] = ['text' => "4 ›", 'callback_data' => "{$this->name}_4_{$updateId}"];
            $keyboard[] = ['text' => "$pages »", 'callback_data' => "{$this->name}_{$pages}_{$updateId}"];

            return $keyboard;
        }

        if ($currentPage > $pages - 4) {
            $keyboard[] = ['text' => "« 1", 'callback_data' => "{$this->name}_1_{$updateId}"];
            $prev = $pages - 4;
            $keyboard[] = ['text' => "‹ {$prev}", 'callback_data' => "{$this->name}_1_{$updateId}"];

            for ($page = $pages - 2; $page <= $pages; $page++) {
                $pageText = $page == $currentPage ? " • $page • " : $page;
                $keyboard[] = ['text' => $pageText, 'callback_data' => "{$this->name}_{$page}_{$updateId}"];
            }

            return $keyboard;
        }

        $prev = $currentPage - 1;
        $next = $currentPage + 1;

        $keyboard[] = ['text' => "« 1", 'callback_data' => "{$this->name}_1_{$updateId}"];
        $keyboard[] = ['text' => "‹ $prev", 'callback_data' => "{$this->name}_{$prev}_{$updateId}"];
        $keyboard[] = ['text' => " • $currentPage • ", 'callback_data' => "{$this->name}_{$currentPage}_{$updateId}"];
        $keyboard[] = ['text' => "$next ›", 'callback_data' => "{$this->name}_{$next}_{$updateId}"];
        $keyboard[] = ['text' => "$pages »", 'callback_data' => "{$this->name}_{$pages}_{$updateId}"];

        return $keyboard;
    }
}