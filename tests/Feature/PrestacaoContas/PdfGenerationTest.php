<?php

use function Pest\Laravel\actingAs;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
});

it('gera pdf da prestacao de contas', function () {
    $response = actingAs($this->user)
        ->post(route('prestacao-contas.pdf'), [
            'mes' => now()->month,
            'ano' => now()->year,
            '_token' => csrf_token(),
        ]);

    $response->assertOk();
    expect(str_starts_with($response->headers->get('Content-Type'), 'application/pdf'))->toBeTrue();
});

it('gera pdf da prestacao de contas para periodo de varios meses', function () {
    $response = actingAs($this->user)
        ->post(route('prestacao-contas.pdf'), [
            'mes_inicio' => 1,
            'ano_inicio' => 2025,
            'mes_fim' => 3,
            'ano_fim' => 2025,
            '_token' => csrf_token(),
        ]);

    $response->assertOk();
    expect(str_starts_with($response->headers->get('Content-Type'), 'application/pdf'))->toBeTrue();
});

it('saldo final da prestacao ignora lancamentos legado', function () {
    $user = $this->user;
    $benfeitor = \App\Models\Benfeitor::factory()->create();

    \App\Models\Lancamento::create([
        'data' => '2026-02-10',
        'tipo' => \App\Enums\TipoLancamentoEnum::Entrada,
        'categoria' => \App\Enums\CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 9000,
        'descricao' => 'Conta antiga CS',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => true,
    ]);

    \App\Models\Lancamento::create([
        'data' => '2026-03-10',
        'tipo' => \App\Enums\TipoLancamentoEnum::Entrada,
        'categoria' => \App\Enums\CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 150,
        'descricao' => 'Conta atual',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => false,
    ]);

    $saldo = app(\App\Services\SaldoService::class);
    $inicioMarco = \Carbon\Carbon::create(2026, 3, 1)->startOfMonth();
    $fimMarco = $inicioMarco->copy()->endOfMonth();

    $saldoAnterior = $saldo->saldoAnterior(3, 2026);
    $saldoFinal = $saldoAnterior
        + $saldo->totalEntradasPeriodo($inicioMarco, $fimMarco)
        - $saldo->totalSaidasPeriodo($inicioMarco, $fimMarco);

    expect($saldoAnterior)->toBe(0.0);
    expect($saldoFinal)->toBe(150.0);
});
