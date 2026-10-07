<?php

use App\Models\Lancamento;
use App\Services\ExtratoBancarioParser;
use App\Services\SaldoService;
use App\Support\ExtratoBancarioCatalogo;
use Database\Seeders\ExtratoAgo2026Seeder;
use Database\Seeders\ExtratoContaAtualSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;

it('interpreta o extrato de ago/2026', function () {
    $parser = new ExtratoBancarioParser;
    $linhas = $parser->parse(database_path(ExtratoAgo2026Seeder::CSV));

    expect($linhas)->toHaveCount(10);

    $creditos = collect($linhas)->where('tipo', 'entrada')->sum('valor');
    $debitos = collect($linhas)->where('tipo', 'saida')->sum('valor');

    expect(round($creditos, 2))->toBe(0.08);
    expect(round($debitos, 2))->toBe(664.24);
});

it('importa somente os lancamentos do extrato ago/2026 sem duplicar', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ExtratoContaAtualSeeder::class);
    $this->seed(ExtratoAgo2026Seeder::class);

    expect(Lancamento::query()->where('documento', '1837231')->count())->toBe(1);
    expect(Lancamento::query()->where('documento', '1219264')->count())->toBe(0);

    $keila = Lancamento::query()->where('documento', '1837231')->first();
    expect($keila?->descricao)->toBe('Keila Silva Moreira');
    expect($keila?->classificado)->toBeTrue();
    expect((float) $keila?->valor)->toBe(75.0);

    $klara = Lancamento::query()->where('documento', '1918179')->first();
    expect($klara?->descricao)->toBe('Comercial Klara');

    $centro = Lancamento::query()->where('documento', '1818564')->first();
    expect($centro?->descricao)->toBe('Centro Administrativo');
    expect((float) $centro?->valor)->toBe(241.72);

    $antes = Lancamento::contaAtual()->count();
    $this->seed(ExtratoAgo2026Seeder::class);
    expect(Lancamento::contaAtual()->count())->toBe($antes);

    $saldo = app(SaldoService::class)->saldoAcumulado();
    expect($saldo)->toBe(518.79);
});

it('remove pix de 03/09 se tiver sido importado por engano', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);

    $user = \App\Models\User::query()->first();

    Lancamento::create([
        'data' => '2026-09-03',
        'tipo' => 'entrada',
        'categoria' => 'arrecadacao',
        'valor' => 30,
        'descricao' => 'PIX recebido — Sandra Regina J. Salgado',
        'user_id' => $user->id,
        'classificado' => true,
        'is_historico' => false,
        'historico_bancario' => 'PIX RECEBIDO',
        'documento' => '1219264',
    ]);

    $this->seed(ExtratoAgo2026Seeder::class);

    expect(Lancamento::query()->where('documento', '1219264')->count())->toBe(0);
    expect(Lancamento::query()->where('documento', '1948232')->count())->toBe(1);
});

it('cataloga o extrato ago/2026 versionado no projeto', function () {
    $todos = ExtratoBancarioCatalogo::todos();
    $novo = collect($todos)->firstWhere('arquivo', '2026-08-11-a-2026-08-31_bradesco-ag2617-conta-71077-6.pdf');

    expect($novo)->not->toBeNull();
    expect($novo['existe'])->toBeTrue();
    expect($novo['seeder'])->toBe('ExtratoAgo2026Seeder');
    expect($novo['inicio'])->toBe('2026-08-11');
    expect($novo['fim'])->toBe('2026-08-31');
});
