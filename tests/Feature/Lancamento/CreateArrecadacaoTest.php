<?php

use App\Actions\Lancamento\CreateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\Lancamento;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
    $this->benfeitor = Benfeitor::factory()->create();
});

it('cria arrecadacao com benfeitor', function () {
    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 500,
        'descricao' => 'Doação mensal',
        'benfeitor_id' => $this->benfeitor->id,
    ], $this->user->id);

    expect($lancamento->tipo)->toBe(TipoLancamentoEnum::Entrada);
    expect($lancamento->categoria)->toBe(CategoriaLancamentoEnum::Arrecadacao);
    expect($lancamento->benfeitor_id)->toBe($this->benfeitor->id);
    expect($lancamento->classificado)->toBeTrue();
});

it('cria arrecadacao cadastrando novo benfeitor pelo nome', function () {
    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 80,
        'descricao' => 'PIX recebido',
        'novo_benfeitor_nome' => 'Maria da Silva',
    ], $this->user->id);

    expect($lancamento->benfeitor->nome)->toBe('Maria da Silva');
    expect($lancamento->benfeitor->membro_id)->toBe($this->user->id);
    expect(Benfeitor::where('nome', 'Maria da Silva')->exists())->toBeTrue();
});

it('permite arrecadacao sem benfeitor quando nao classificada', function () {
    $action = app(CreateLancamentoAction::class);
    $lancamento = $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 50,
        'descricao' => 'PIX recebido',
        'classificado' => false,
    ], $this->user->id);

    expect($lancamento->benfeitor_id)->toBeNull();
    expect($lancamento->classificado)->toBeFalse();
});

it('exige benfeitor em arrecadacao classificada', function () {
    $action = app(CreateLancamentoAction::class);

    $action->execute([
        'data' => now()->format('Y-m-d'),
        'tipo' => TipoLancamentoEnum::Entrada->value,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao->value,
        'valor' => 50,
        'descricao' => 'PIX recebido',
        'classificado' => true,
    ], $this->user->id);
})->throws(ValidationException::class);
