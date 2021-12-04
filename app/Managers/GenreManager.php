<?php

namespace App\Managers;

use App\Models\Genre;
use App\Models\Stat;
use Illuminate\Support\Collection;

class GenreManager
{
    private ?Genre $genre;
    private StatManager $statManager;

    public function __construct(Genre $genre = null)
    {
        $this->genre = $genre;
        $this->statManager = app(StatManager::class);
    }

    /**
     * @param array $genres
     * @return Genre[]
     */
    public function bulkCreateOrUpdate(array $genres): Collection
    {
        $created = collect();

        foreach ($genres as $genre) {
            $created->push($this->createOrUpdate(['name' => $genre]));
        }

        return $created;
    }

    public function firstOrCreate(array $params): Genre
    {
        $builder = Genre::query();
        foreach ($params as $field => $value) {
            $builder->where($field, '=', $value);
        }

        $this->genre = $builder->first();
        if ($this->genre) {
            return $this->genre;
        } else {
            return $this->create($params);
        }
    }

    public function createOrUpdate(array $params): Genre
    {
        $builder = Genre::query();
        foreach ($params as $field => $value) {
            $builder->where($field, '=', $value);
        }

        $this->genre = $builder->first();
        if ($this->genre) {
            return $this->update($params);
        } else {
            return $this->create($params);
        }
    }

    public function create(array $params): Genre
    {
        $this->genre = app(Genre::class);
        $this->genre->fill($params);
        $this->genre->save();

        $this->statManager->create(Stat::GENRE_MODEL, $this->genre->id, Stat::CREATED_ACTION);

        return $this->genre;
    }

    public function update(array $params): Genre
    {
        $this->genre->fill($params);
        $this->genre->save();

        return $this->genre;
    }
}
