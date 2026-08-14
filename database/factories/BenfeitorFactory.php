<?php

namespace Database\Factories;

use App\Models\Benfeitor;
use App\Models\User;
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
            'membro_id' => User::factory(),
            'ativo' => true,
            'observacao' => null,
        ];
    }
}
