<?php

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\SaldoService;
use Carbon\Carbon;

beforeEach(function () {
    $this->saldoService = new SaldoService;
});

it('calcula saldo correto com entradas e saidas', function () {
    $user = User::factory()->create();
    $benfeitor = Benfeitor::factory()->create();

    Lancamento::create([
        'data' => now()->subDays(5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 1000,
        'descricao' => 'Arrecadação teste',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
    ]);

    Lancamento::create([
        'data' => now()->subDays(3),
        'tipo' => TipoLancamentoEnum::Saida,
        'categoria' => CategoriaLancamentoEnum::Compra,
        'valor' => 300,
        'descricao' => 'Compra teste',
        'user_id' => $user->id,
    ]);

    expect($this->saldoService->saldoAcumulado())->toBe(700.0);
});

it('calcula saldo periodo corretamente', function () {
    $user = User::factory()->create();
    $benfeitor = Benfeitor::factory()->create();

    $inicio = Carbon::create(2025, 2, 1);
    $fim = Carbon::create(2025, 2, 28);

    Lancamento::create([
        'data' => Carbon::create(2025, 2, 5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 500,
        'descricao' => 'Arrecadação fev',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
    ]);

    Lancamento::create([
        'data' => Carbon::create(2025, 2, 15),
        'tipo' => TipoLancamentoEnum::Saida,
        'categoria' => CategoriaLancamentoEnum::Compra,
        'valor' => 200,
        'descricao' => 'Compra fev',
        'user_id' => $user->id,
    ]);

    expect($this->saldoService->totalEntradasPeriodo($inicio, $fim))->toBe(500.0);
    expect($this->saldoService->totalSaidasPeriodo($inicio, $fim))->toBe(200.0);
    expect($this->saldoService->saldoPeriodo($inicio, $fim))->toBe(300.0);
});

it('saldo anterior retorna zero quando nao ha lancamentos anteriores', function () {
    expect($this->saldoService->saldoAnterior(2, 2025))->toBe(0.0);
});

it('reembolso nao afeta o saldo', function () {
    $user = User::factory()->create();
    $benfeitor = Benfeitor::factory()->create();

    Lancamento::create([
        'data' => now()->subDays(5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 1000,
        'descricao' => 'Arrecadação',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
    ]);

    Lancamento::create([
        'data' => now()->subDays(3),
        'tipo' => TipoLancamentoEnum::Saida,
        'categoria' => CategoriaLancamentoEnum::Reembolso,
        'valor' => 200,
        'descricao' => 'Reembolso membro',
        'user_id' => $user->id,
    ]);

    Lancamento::create([
        'data' => now()->subDays(2),
        'tipo' => TipoLancamentoEnum::Saida,
        'categoria' => CategoriaLancamentoEnum::Compra,
        'valor' => 300,
        'descricao' => 'Compra',
        'user_id' => $user->id,
    ]);

    expect($this->saldoService->saldoAcumulado())->toBe(700.0);
});

it('saldo em conta e saldo em maos', function () {
    $user = User::factory()->create();
    $benfeitor = Benfeitor::factory()->create();

    Lancamento::create([
        'data' => now()->subDays(5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 1000,
        'descricao' => 'Arrecadação teste',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
    ]);

    Lancamento::create([
        'data' => now()->subDays(3),
        'tipo' => TipoLancamentoEnum::Saida,
        'categoria' => CategoriaLancamentoEnum::Compra,
        'valor' => 300,
        'descricao' => 'Compra teste',
        'user_id' => $user->id,
    ]);

    ControleSaldo::registro()->update(['saldo_em_maos' => 200]);

    expect($this->saldoService->saldoAcumulado())->toBe(700.0);
    expect($this->saldoService->saldoEmMaos())->toBe(200.0);
    expect($this->saldoService->saldoEmConta())->toBe(500.0);
});

it('separa saldo legado da conta antiga do saldo da conta atual', function () {
    $user = User::factory()->create();
    $benfeitor = Benfeitor::factory()->create();

    Lancamento::create([
        'data' => now()->subYear(),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 5000,
        'descricao' => 'Conta antiga',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => true,
    ]);

    Lancamento::create([
        'data' => now(),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 200,
        'descricao' => 'Conta nova',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => false,
    ]);

    expect($this->saldoService->saldoAcumulado())->toBe(200.0);
    expect($this->saldoService->saldoLegado())->toBe(5000.0);
    expect($this->saldoService->saldoLivro())->toBe(5200.0);
    expect($this->saldoService->saldoEmConta())->toBe(200.0);
    expect($this->saldoService->saldoEmConta())->not->toBe($this->saldoService->saldoLivro());
});
