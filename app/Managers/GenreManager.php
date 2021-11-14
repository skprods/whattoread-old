<?php

namespace App\Managers;

use App\Models\Genre;
use Illuminate\Support\Collection;

class GenreManager
{
    private ?Genre $genre;

    public function __construct(Genre $genre = null)
    {
        $this->genre = $genre;
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

        return $this->genre;
    }

    public function update(array $params): Genre
    {
        $this->genre->fill($params);
        $this->genre->save();

        return $this->genre;
    }
}
