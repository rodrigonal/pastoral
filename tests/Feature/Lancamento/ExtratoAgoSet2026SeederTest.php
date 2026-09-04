<?php

use App\Models\Benfeitor;
use App\Models\Lancamento;
use App\Services\ExtratoBancarioParser;
use App\Services\SaldoService;
use App\Support\ExtratoBancarioCatalogo;
use Database\Seeders\ExtratoAgoSet2026Seeder;
use Database\Seeders\ExtratoContaAtualSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;

it('interpreta o extrato de ago/set 2026', function () {
    $parser = new ExtratoBancarioParser;
    $linhas = $parser->parse(database_path('seeders/data/extrato-2026-08-17-a-2026-09-03.csv'));

    expect($linhas)->toHaveCount(11);

    $creditos = collect($linhas)->where('tipo', 'entrada')->sum('valor');
    $debitos = collect($linhas)->where('tipo', 'saida')->sum('valor');

    expect(round($creditos, 2))->toBe(30.08);
    expect(round($debitos, 2))->toBe(664.24);
});

it('importa o extrato ago/set 2026 sem duplicar', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ExtratoContaAtualSeeder::class);
    $this->seed(ExtratoAgoSet2026Seeder::class);

    expect(Lancamento::query()->where('documento', '1837231')->count())->toBe(1);
    expect(Lancamento::query()->where('documento', '1219264')->count())->toBe(1);

    $keila = Lancamento::query()->where('documento', '1837231')->first();
    expect($keila?->descricao)->toBe('Keila Silva Moreira');
    expect($keila?->classificado)->toBeTrue();
    expect((float) $keila?->valor)->toBe(75.0);

    $sandra = Benfeitor::query()->where('nome', 'Sandra Regina J. Salgado')->first();
    expect($sandra)->not->toBeNull();

    $pix = Lancamento::query()->where('documento', '1219264')->first();
    expect($pix?->benfeitor_id)->toBe($sandra->id);
    expect($pix?->descricao)->toContain('Sandra Regina J. Salgado');
    expect($pix?->classificado)->toBeTrue();

    $klara = Lancamento::query()->where('documento', '1918179')->first();
    expect($klara?->descricao)->toBe('Comercial Klara');

    $antes = Lancamento::contaAtual()->count();
    $this->seed(ExtratoAgoSet2026Seeder::class);
    expect(Lancamento::contaAtual()->count())->toBe($antes);

    $saldo = app(SaldoService::class)->saldoAcumulado();
    expect($saldo)->toBe(548.79);
});

it('cataloga o extrato ago/set 2026 versionado no projeto', function () {
    $todos = ExtratoBancarioCatalogo::todos();
    $novo = collect($todos)->firstWhere('arquivo', '2026-08-a-2026-09_bradesco-ag2617-conta-71077-6.pdf');

    expect($novo)->not->toBeNull();
    expect($novo['existe'])->toBeTrue();
    expect($novo['seeder'])->toBe('ExtratoAgoSet2026Seeder');
});
