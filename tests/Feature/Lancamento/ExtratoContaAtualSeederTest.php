<?php

use App\Models\Lancamento;
use App\Services\ExtratoBancarioParser;
use App\Services\SaldoService;
use Database\Seeders\ExtratoContaAtualSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;

it('interpreta o extrato da conta atual', function () {
    $parser = new ExtratoBancarioParser;
    $linhas = $parser->parse(database_path('seeders/data/extrato-conta-atual.csv'));

    expect($linhas)->not->toBeEmpty();

    $entradas = collect($linhas)->where('tipo', 'entrada');
    $saidas = collect($linhas)->where('tipo', 'saida');
    $pixRecebidos = $entradas->where('categoria', 'arrecadacao');
    $rendimentos = $entradas->where('classificado', true);

    expect($pixRecebidos->every(fn ($l) => $l['classificado'] === false))->toBeTrue();
    expect($pixRecebidos->every(fn ($l) => $l['descricao'] === 'PIX recebido'))->toBeTrue();
    expect($saidas->every(fn ($l) => $l['classificado'] === false))->toBeTrue();
    expect($rendimentos)->not->toBeEmpty();
    expect($rendimentos->every(fn ($l) => str_contains($l['descricao'], 'Rendimento')))->toBeTrue();

    $saldo = round($entradas->sum('valor') - $saidas->sum('valor'), 2);
    expect($saldo)->toBe(1182.95);
});

it('importa o extrato como lancamentos da conta atual', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ExtratoContaAtualSeeder::class);

    expect(Lancamento::contaAtual()->count())->toBeGreaterThan(0);
    expect(Lancamento::historico()->count())->toBe(0);
    expect(Lancamento::pendentes()->count())->toBeGreaterThan(0);
    expect(Lancamento::contaAtual()->where('classificado', true)->count())->toBe(2);

    $saldo = app(SaldoService::class)->saldoAcumulado();
    expect($saldo)->toBe(1182.95);
});
