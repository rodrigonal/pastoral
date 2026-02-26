<?php

use App\Actions\Lancamento\CreateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\Segmento;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
    $this->segmento = Segmento::factory()->create();
});

it('cria entrada com dados validos', function () {
    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 150.50,
        'descricao' => 'Arrecadação teste',
        'segmento_ids' => [$this->segmento->id],
    ], $this->user->id);

    expect(Lancamento::count())->toBe(1);
    expect($lancamento->tipo)->toBe(TipoLancamentoEnum::Entrada);
    expect($lancamento->categoria)->toBe(CategoriaLancamentoEnum::Arrecadacao);
    expect((float) $lancamento->valor)->toBe(150.50);
});
