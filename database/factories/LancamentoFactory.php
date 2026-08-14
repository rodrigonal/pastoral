<?php

namespace Database\Factories;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\Lancamento;
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
            if ($lancamento->categoria->requerBenfeitor() && $lancamento->benfeitor_id === null && $lancamento->classificado) {
                $lancamento->update([
                    'benfeitor_id' => Benfeitor::factory()->create()->id,
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoria = fake()->randomElement(CategoriaLancamentoEnum::cases());
        $tipo = $categoria->requerBenfeitor() ? TipoLancamentoEnum::Entrada : TipoLancamentoEnum::Saida;

        return [
            'data' => fake()->dateTimeBetween('-1 year'),
            'tipo' => $tipo,
            'categoria' => $categoria,
            'valor' => fake()->randomFloat(2, 10, 5000),
            'descricao' => fake()->sentence(),
            'observacao' => fake()->optional(0.3)->paragraph(),
            'user_id' => User::factory(),
            'benfeitor_id' => null,
            'classificado' => true,
            'is_historico' => false,
        ];
    }

    public function entrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoLancamentoEnum::Entrada,
            'categoria' => CategoriaLancamentoEnum::Arrecadacao,
            'benfeitor_id' => Benfeitor::factory(),
        ]);
    }

    public function saida(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => TipoLancamentoEnum::Saida,
            'categoria' => CategoriaLancamentoEnum::Compra,
        ]);
    }

    public function historico(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_historico' => true,
        ]);
    }

    public function pendente(): static
    {
        return $this->state(fn (array $attributes) => [
            'classificado' => false,
            'benfeitor_id' => null,
        ]);
    }
}
