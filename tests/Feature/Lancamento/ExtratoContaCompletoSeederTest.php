<?php

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\Lancamento;
use App\Models\LancamentoLegadoCs;
use App\Models\User;
use App\Services\ExtratoBancarioParser;
use App\Services\SaldoService;
use App\Support\ExtratoBancarioCatalogo;
use Database\Seeders\ExtratoContaCompletoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;

it('interpreta o extrato completo ate outubro/2026', function () {
    $parser = new ExtratoBancarioParser;
    $linhas = $parser->parse(database_path(ExtratoContaCompletoSeeder::CSV));

    expect($linhas)->toHaveCount(135);

    $creditos = collect($linhas)->where('tipo', 'entrada')->sum('valor');
    $debitos = collect($linhas)->where('tipo', 'saida')->sum('valor');

    expect(round($creditos - $debitos, 2))->toBe(422.87);
});

it('importa o extrato completo com contrapartes e sem duplicar', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(ExtratoContaCompletoSeeder::class);

    expect(Lancamento::contaAtual()->count())->toBe(135);
    expect(Lancamento::historico()->count())->toBe(0);

    $casa = Benfeitor::query()->where('nome', 'Casa A F Prediletos')->first();
    expect($casa)->not->toBeNull();
    expect(Lancamento::query()->where('documento', '1752176')->value('benfeitor_id'))->toBe($casa->id);

    $klara = Lancamento::query()->where('documento', '1918179')->first();
    expect($klara?->descricao)->toBe('Comercial Klara');

    $sandra = Lancamento::query()->where('documento', '1129593')->first();
    expect($sandra?->descricao)->toContain('Sandra Regina J. Salgado');
    expect($sandra?->classificado)->toBeTrue();

    $antes = Lancamento::contaAtual()->count();
    $this->seed(ExtratoContaCompletoSeeder::class);
    expect(Lancamento::contaAtual()->count())->toBe($antes);

    expect(app(SaldoService::class)->saldoAcumulado())->toBe(422.87);
    expect(app(SaldoService::class)->saldoEmConta())->toBe(422.87);
});

it('arquiva saldo antigo em tabela separada e zera lancamentos antes de reimportar', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(UserSeeder::class);

    $user = User::query()->first();

    $legado = Lancamento::create([
        'data' => '2025-06-10',
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 629.37,
        'descricao' => 'Saldo antigo CS',
        'user_id' => $user->id,
        'is_historico' => true,
    ]);

    Lancamento::create([
        'data' => '2026-08-01',
        'tipo' => TipoLancamentoEnum::Entrada,
        'categoria' => CategoriaLancamentoEnum::Arrecadacao,
        'valor' => 50,
        'descricao' => 'Duplicata a limpar',
        'user_id' => $user->id,
        'is_historico' => false,
        'historico_bancario' => 'PIX RECEBIDO',
        'documento' => '9999999',
    ]);

    // A migration já criou a tabela no RefreshDatabase; aqui validamos o efeito
    // arquivo → wipe → reimportação do extrato completo.
    \Illuminate\Support\Facades\DB::table('lancamentos_legado_cs')->insertOrIgnore([
        'original_lancamento_id' => $legado->id,
        'data' => $legado->data->format('Y-m-d'),
        'tipo' => $legado->tipo->value,
        'categoria' => $legado->categoria->value,
        'valor' => $legado->valor,
        'descricao' => $legado->descricao,
        'observacao' => null,
        'user_id' => $user->id,
        'benfeitor_id' => null,
        'classificado' => true,
        'historico_bancario' => null,
        'documento' => null,
        'original_created_at' => now(),
        'original_updated_at' => now(),
        'arquivado_em' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    \Illuminate\Support\Facades\DB::table('lancamentos')->delete();
    $this->seed(ExtratoContaCompletoSeeder::class);

    expect(Lancamento::query()->where('documento', '9999999')->count())->toBe(0);
    expect(LancamentoLegadoCs::query()->where('original_lancamento_id', $legado->id)->count())->toBe(1);
    expect(Lancamento::contaAtual()->count())->toBe(135);
    expect(app(SaldoService::class)->saldoLegado())->toBe(629.37);
    expect(app(SaldoService::class)->saldoAcumulado())->toBe(422.87);
    expect(app(SaldoService::class)->saldoLivro())->toBe(1052.24);
});

it('cataloga o extrato completo versionado no projeto', function () {
    $todos = ExtratoBancarioCatalogo::todos();
    $completo = collect($todos)->firstWhere('arquivo', '2026-01-a-2026-10_bradesco-ag2617-conta-71077-6.pdf');

    expect($completo)->not->toBeNull();
    expect($completo['existe'])->toBeTrue();
    expect($completo['seeder'])->toBe('ExtratoContaCompletoSeeder');
});
