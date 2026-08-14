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

    $legadoAntigo = Lancamento::create([
        'data' => '2025-06-10',
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 800,
        'descricao' => 'Doação na conta antiga',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => false,
    ]);
    $legadoAntigo->forceFill(['created_at' => '2025-06-10 10:00:00'])->saveQuietly();

    $legadoAposMarco = Lancamento::create([
        'data' => '2026-04-15',
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 610.43,
        'descricao' => 'Doação CS depois de março',
        'user_id' => $user->id,
        'benfeitor_id' => $benfeitor->id,
        'is_historico' => false,
    ]);
    $legadoAposMarco->forceFill(['created_at' => '2026-04-15 10:00:00'])->saveQuietly();

    $this->seed(ExtratoContaAtualSeeder::class);
    $this->seed(ClassificarLancamentosLegadoSeeder::class);

    expect($legadoAntigo->fresh()->is_historico)->toBeTrue();
    expect($legadoAposMarco->fresh()->is_historico)->toBeTrue();
    expect(Lancamento::historico()->count())->toBe(2);

    $saldo = app(SaldoService::class);
    expect($saldo->saldoLegado())->toBe(1410.43);
    expect($saldo->saldoAcumulado())->toBe(1182.95);
    expect($saldo->saldoEmConta())->toBe(1182.95);
    expect((float) ControleSaldo::registro()->saldo_legado)->toBe(1410.43);
});
