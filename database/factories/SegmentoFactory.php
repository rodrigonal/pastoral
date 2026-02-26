<?php

namespace Database\Factories;

use App\Models\Segmento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Segmento>
 */
class SegmentoFactory extends Factory
{
    protected $model = Segmento::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->words(2, true),
            'ordem' => fake()->numberBetween(1, 100),
            'ativo' => true,
        ];
    }
}
