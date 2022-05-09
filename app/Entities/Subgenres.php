<?php

namespace App\Entities;

use Illuminate\Support\Facades\DB;

class Subgenres
{
    private array $subgenres;

    public function __construct()
    {
        $this->subgenres = DB::table('subgenres')
            ->get()
            ->mapToGroups(function ($subgenre) {
                return [$subgenre->child_id => $subgenre->parent_id];
            })
            ->toArray();
    }

    public function getGenres(int $genreId): array
    {
        $genres = [];
        $this->getParentGenres($genreId, $genres);

        return $genres;
    }

    public function getTopGenres(int $genreId): array
    {
        $genres = [];
        $this->getParentGenres($genreId, $genres, false);

        return $genres;
    }

    private function getParentGenres(int $genreId, array &$genres, bool $all = true): void
    {
        if (isset($this->subgenres[$genreId])) {
            foreach ($this->subgenres[$genreId] as $subgenreId) {
                if ($subgenreId === $genreId) {
                    continue;
                }

                $this->getParentGenres($subgenreId, $genres);
                if ($all) {
                    $genres[$subgenreId] = $subgenreId;
                }
            }
        } else {
            $genres[$genreId] = $genreId;
        }
    }
}