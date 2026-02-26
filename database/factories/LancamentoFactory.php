<?php

namespace Database\Factories;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\Segmento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lancamento>
 */
class LancamentoFactory extends Factory
{
    protected $model = Lancamento::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Lancamento $lancamento) {
            if ($lancamento->categoria->requerSegmento() && $lancamento->segmentos()->count() === 0) {
                $lancamento->segmentos()->attach(Segmento::factory());
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoria = fake()->randomElement(CategoriaLancamentoEnum::cases());
        $tipo = $categoria->requerSegmento() ? TipoLancamentoEnum::Entrada : TipoLancamentoEnum::Saida;

        return [
            'data' => fake()->dateTimeBetween('-1 year'),
            'tipo' => $tipo,
            'categoria' => $categoria,
            'valor' => fake()->randomFloat(2, 10, 5000),
            'descricao' => fake()->sentence(),
            'observacao' => fake()->optional(0.3)->paragraph(),
            'anexo_path' => null,
            'user_id' => User::factory(),
        ];
    }

    public function entrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoLancamentoEnum::Entrada,
            'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        ])->afterCreating(function (Lancamento $lancamento) {
            $lancamento->segmentos()->attach(Segmento::factory());
        });
    }

    public function saida(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoLancamentoEnum::Saida,
            'categoria' => CategoriaLancamentoEnum::Compra,
        ]);
    }
}
