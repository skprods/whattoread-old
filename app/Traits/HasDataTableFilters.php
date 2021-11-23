<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasDataTableFilters
{
    public function filterInteger(string $column): array
    {
        return [
            $column,
            static function (Builder $query, $keyword) use ($column) {
                if (!is_numeric($keyword)) {
                    return $query;
                }

                return $query->where($column, $keyword);
            }
        ];
    }

    public function filterDate(string $column): array
    {
        return [
            $column,
            static function (Builder $query, $keyword) use ($column) {
                $range = explode('_', $keyword);

                if (count($range) === 2) {
                    return $query->whereBetween($column, $range);
                } else {
                    return $query->whereDate($column, $range[0]);
                }
            }
        ];
    }
}
