<?php

use App\Actions\Lancamento\CreateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
});

it('cria saida com dados validos', function () {
    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Saida->value,
        'categoria' => CategoriaLancamentoEnum::Compra->value,
        'valor' => 250,
        'descricao' => 'Compra de alimentos',
    ], $this->user->id);

    expect(Lancamento::count())->toBe(1);
    expect($lancamento->tipo)->toBe(TipoLancamentoEnum::Saida);
    expect($lancamento->categoria)->toBe(CategoriaLancamentoEnum::Compra);
});
