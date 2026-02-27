<?php

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\Segmento;
use App\Models\User;
use App\Services\SaldoService;
use Carbon\Carbon;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->saldoService = new SaldoService;
});

it('calcula saldo correto com entradas e saidas', function () {
    $user = User::factory()->create();
    $segmento = Segmento::factory()->create();

    $lanc1 = Lancamento::create([
        'data' => now()->subDays(5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 1000,
        'descricao' => 'Arrecadação teste',
        'user_id' => $user->id,
    ]);
    $lanc1->segmentos()->attach($segmento);

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
    $segmento = Segmento::factory()->create();

    $inicio = Carbon::create(2025, 2, 1);
    $fim = Carbon::create(2025, 2, 28);

    $lanc1 = Lancamento::create([
        'data' => Carbon::create(2025, 2, 5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 500,
        'descricao' => 'Arrecadação fev',
        'user_id' => $user->id,
    ]);
    $lanc1->segmentos()->attach($segmento);

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
    $segmento = Segmento::factory()->create();

    $lanc1 = Lancamento::create([
        'data' => now()->subDays(5),
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 1000,
        'descricao' => 'Arrecadação',
        'user_id' => $user->id,
    ]);
    $lanc1->segmentos()->attach($segmento);

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
