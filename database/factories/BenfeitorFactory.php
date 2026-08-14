<?php

namespace Database\Factories;

use App\Models\Benfeitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Benfeitor>
 */
class BenfeitorFactory extends Factory
{
    protected $model = Benfeitor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->name(),
            'ativo' => true,
            'observacao' => null,
        ];
    }
}
