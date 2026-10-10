<?php

namespace Database\Factories;

use App\Models\BookType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BookType> */
class BookTypeFactory extends Factory
{
    protected $model = BookType::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->word()];
    }
}
