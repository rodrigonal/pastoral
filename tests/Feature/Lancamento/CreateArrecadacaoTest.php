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

it('cria arrecadacao com segmento', function () {
    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 500,
        'descricao' => 'Arrecadação mensal Freis',
        'segmento_ids' => [$this->segmento->id],
    ], $this->user->id);

    expect($lancamento->tipo)->toBe(TipoLancamentoEnum::Entrada);
    expect($lancamento->categoria)->toBe(CategoriaLancamentoEnum::Arrecadacao);
    expect($lancamento->segmentos->pluck('id')->toArray())->toContain($this->segmento->id);
});
