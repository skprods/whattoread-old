<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->text(40),
            'description' => $this->faker->text(350),
            'author' => $this->faker->firstName() . " " . $this->faker->lastName(),
        ];
    }
}
