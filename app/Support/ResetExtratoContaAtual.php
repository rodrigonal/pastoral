<?php

namespace App\Support;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use App\Services\SaldoService;
use Database\Seeders\ExtratoContaCompletoSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetExtratoContaAtual
{
    /**
     * Arquiva saldo antigo (se ainda estiver em lancamentos), zera a tabela e reimporta o extrato completo.
     *
     * @return array{
     *     arquivados: int,
     *     lancamentos_importados: int,
     *     saldo_acumulado: float,
     *     saldo_em_conta: float,
     *     saldo_legado: float
     * }
     */
    public static function executar(): array
    {
        self::garantirTabelaLegado();

        $agora = now();
        $arquivados = 0;

        if (Schema::hasColumn('lancamentos', 'is_historico')) {
            $legado = DB::table('lancamentos')->where('is_historico', true)->orderBy('id')->get();

            foreach ($legado as $l) {
                $inseriu = DB::table('lancamentos_legado_cs')->insertOrIgnore([
                    'original_lancamento_id' => $l->id,
                    'data' => $l->data,
                    'tipo' => $l->tipo,
                    'categoria' => $l->categoria,
                    'valor' => $l->valor,
                    'descricao' => $l->descricao,
                    'observacao' => $l->observacao,
                    'user_id' => $l->user_id,
                    'benfeitor_id' => $l->benfeitor_id,
                    'classificado' => (bool) $l->classificado,
                    'historico_bancario' => $l->historico_bancario,
                    'documento' => $l->documento,
                    'original_created_at' => $l->created_at,
                    'original_updated_at' => $l->updated_at,
                    'arquivado_em' => $agora,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);

                if ($inseriu) {
                    $arquivados++;
                }
            }
        }

        self::atualizarSaldoLegadoGravado();

        DB::table('lancamentos')->delete();

        Artisan::call('db:seed', [
            '--class' => ExtratoContaCompletoSeeder::class,
            '--force' => true,
        ]);

        if (class_exists(ClassificacaoContaLancamentos::class)
            && Schema::hasColumn('lancamentos', 'is_historico')) {
            ClassificacaoContaLancamentos::executar();
        }

        $saldo = app(SaldoService::class);

        return [
            'arquivados' => $arquivados,
            'lancamentos_importados' => Lancamento::contaAtual()->count(),
            'saldo_acumulado' => $saldo->saldoAcumulado(),
            'saldo_em_conta' => $saldo->saldoEmConta(),
            'saldo_legado' => $saldo->saldoLegado(),
        ];
    }

    public static function garantirTabelaLegado(): void
    {
        if (Schema::hasTable('lancamentos_legado_cs')) {
            return;
        }

        Schema::create('lancamentos_legado_cs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_lancamento_id')->nullable()->unique();
            $table->date('data');
            $table->string('tipo', 10);
            $table->string('categoria', 20);
            $table->decimal('valor', 15, 2);
            $table->string('descricao');
            $table->text('observacao')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('benfeitor_id')->nullable()->constrained('benfeitores')->nullOnDelete();
            $table->boolean('classificado')->default(true);
            $table->string('historico_bancario')->nullable();
            $table->string('documento')->nullable();
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('original_updated_at')->nullable();
            $table->timestamp('arquivado_em')->useCurrent();
            $table->timestamps();

            $table->index('data');
            $table->index('tipo');
            $table->index('categoria');
        });
    }

    public static function atualizarSaldoLegadoGravado(): void
    {
        if (! Schema::hasTable('lancamentos_legado_cs')
            || ! Schema::hasTable('controle_saldos')
            || ! Schema::hasColumn('controle_saldos', 'saldo_legado')) {
            return;
        }

        $entradas = (float) DB::table('lancamentos_legado_cs')
            ->where('tipo', TipoLancamentoEnum::Entrada->value)
            ->sum('valor');

        $saidas = (float) DB::table('lancamentos_legado_cs')
            ->where('tipo', TipoLancamentoEnum::Saida->value)
            ->where('categoria', '!=', CategoriaLancamentoEnum::Reembolso->value)
            ->sum('valor');

        ControleSaldo::registro()->update([
            'saldo_legado' => round($entradas - $saidas, 2),
        ]);
    }
}
