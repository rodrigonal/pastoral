<?php

namespace Database\Factories;

use App\Models\Pastoral;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pastoral>
 */
class PastoralFactory extends Factory
{
    protected $model = Pastoral::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'data' => now(),
            'titulo' => 'Pastoral de Rua · teste',
            'agradecimento' => 'Obrigado, benfeitores.',
            'frase_id' => 1,
            'user_id' => User::factory(),
        ];
    }
}
