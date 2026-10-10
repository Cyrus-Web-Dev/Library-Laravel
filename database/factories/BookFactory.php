<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookType;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'publication_year' => fake()->numberBetween(1990, 2025),
            'genre_id' => Genre::factory(),
            'type_id' => BookType::factory(),
            'description' => fake()->paragraph(),
            'location' => 'Rak '.fake()->randomLetter().'-0'.fake()->numberBetween(1, 9),
            'total_stock' => 3,
            'available_stock' => 3,
            'language' => 'Indonesia',
            'status' => Book::STATUS_AVAILABLE,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['available_stock' => 0]);
    }
}
