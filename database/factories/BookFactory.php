<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Book::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->text(100),
            'description' => $this->faker->text(350),
            'series' => $this->faker->text(15),
            'publisher_name' => $this->faker->text(25),
            'publisher_year' => $this->faker->year,
            'author' => $this->faker->firstName . " " . $this->faker->lastName,
            'category' => $this->faker->text(25),
            'shop_url' => $this->faker->url,
            'shop_name' => $this->faker->text(25),
            'shop_book_id' => $this->faker->numberBetween(1000, 999999),
        ];
    }
}
