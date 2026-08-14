<?php

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use App\Models\User;
use App\Services\SaldoService;
use Database\Seeders\ClassificarLancamentosLegadoSeeder;
use Database\Seeders\ExtratoContaAtualSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;

it('classifica lancamentos do extrato como conta atual e anteriores como legado', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);

    $user = User::query()->first();
    $benfeitor = Benfeitor::factory()->create();

    $legado = Lancamento::create([
        'data' => '2025-06-10',
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 800,
        'descricao' => 'Doação na conta antiga',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => false,
    ]);

    $this->seed(ExtratoContaAtualSeeder::class);
    $this->seed(ClassificarLancamentosLegadoSeeder::class);

    expect($legado->fresh()->is_historico)->toBeTrue();
    expect(Lancamento::contaAtual()->count())->toBeGreaterThan(0);
    expect(Lancamento::historico()->count())->toBe(1);

    $saldo = app(SaldoService::class);
    expect($saldo->saldoLegado())->toBe(800.0);
    expect($saldo->saldoAcumulado())->toBe(1182.95);
    expect((float) ControleSaldo::registro()->saldo_legado)->toBe(800.0);
});
