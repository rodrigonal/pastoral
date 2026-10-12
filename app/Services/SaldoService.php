<?php

namespace App\Services;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use App\Models\LancamentoLegadoCs;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class SaldoService
{
    /**
     * Lançamentos da conta atual (exclui legado da conta antiga).
     */
    public function queryContaAtual(): Builder
    {
        return Lancamento::query()->contaAtual();
    }

    /**
     * Lançamentos da conta antiga (CS) ainda em `lancamentos` (legado não arquivado).
     */
    public function queryLegado(): Builder
    {
        return Lancamento::query()->historico();
    }

    /**
     * Saldo acumulado da conta atual até determinada data (inclusive).
     * Reembolsos não afetam o saldo.
     */
    public function saldoAcumulado(?Carbon $ate = null): float
    {
        return $this->saldoDaQuery($this->queryContaAtual(), $ate);
    }

    /**
     * Saldo do livro da conta antiga (Centro Social): arquivo `lancamentos_legado_cs`
     * + quaisquer registros ainda marcados como histórico em `lancamentos`.
     */
    public function saldoLegado(?Carbon $ate = null): float
    {
        $emLancamentos = $this->saldoDaQuery($this->queryLegado(), $ate);
        $arquivado = $this->saldoLegadoArquivado($ate);

        return round($emLancamentos + $arquivado, 2);
    }

    /**
     * Valor em espécie informado manualmente (não entra nos lançamentos).
     */
    public function saldoEmMaos(): float
    {
        return round((float) ControleSaldo::registro()->saldo_em_maos, 2);
    }

    /**
     * Saldo na conta bancária atual = livro da conta atual − saldo em mãos.
     * Não inclui o saldo antigo da conta do CS.
     */
    public function saldoEmConta(?Carbon $ate = null): float
    {
        return round($this->saldoAcumulado($ate) - $this->saldoEmMaos(), 2);
    }

    /**
     * Soma de conta atual + saldo antigo (arquivo e/ou histórico), exceto reembolsos.
     */
    public function saldoLivro(?Carbon $ate = null): float
    {
        return round($this->saldoAcumulado($ate) + $this->saldoLegado($ate), 2);
    }

    private function saldoLegadoArquivado(?Carbon $ate = null): float
    {
        if (! Schema::hasTable('lancamentos_legado_cs')) {
            return 0.0;
        }

        $query = LancamentoLegadoCs::query();

        return $this->saldoDaQuery($query, $ate);
    }

    private function saldoDaQuery(Builder $query, ?Carbon $ate = null): float
    {
        if ($ate !== null) {
            $query->whereDate('data', '<=', $ate->format('Y-m-d'));
        }

        $entradas = (clone $query)
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->sum('valor');

        $saidas = (clone $query)
            ->where('tipo', TipoLancamentoEnum::Saida)
            ->where('categoria', '!=', CategoriaLancamentoEnum::Reembolso)
            ->sum('valor');

        return round((float) $entradas - (float) $saidas, 2);
    }

    /**
     * Saldo do período (de até ate, inclusive).
     */
    public function saldoPeriodo(Carbon $de, Carbon $ate): float
    {
        $entradas = $this->totalEntradasPeriodo($de, $ate);
        $saidas = $this->totalSaidasPeriodo($de, $ate);

        return round($entradas - $saidas, 2);
    }

    /**
     * Total de entradas no período.
     */
    public function totalEntradasPeriodo(Carbon $de, Carbon $ate): float
    {
        return (float) $this->queryContaAtual()
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->whereDate('data', '>=', $de->format('Y-m-d'))
            ->whereDate('data', '<=', $ate->format('Y-m-d'))
            ->sum('valor');
    }

    /**
     * Total de saídas no período (exclui reembolsos - não afetam saldo).
     */
    public function totalSaidasPeriodo(Carbon $de, Carbon $ate): float
    {
        return (float) $this->queryContaAtual()
            ->where('tipo', TipoLancamentoEnum::Saida)
            ->where('categoria', '!=', CategoriaLancamentoEnum::Reembolso)
            ->whereDate('data', '>=', $de->format('Y-m-d'))
            ->whereDate('data', '<=', $ate->format('Y-m-d'))
            ->sum('valor');
    }

    /**
     * Total de reembolsos no período (apenas para controle no relatório).
     */
    public function totalReembolsosPeriodo(Carbon $de, Carbon $ate): float
    {
        return (float) $this->queryContaAtual()
            ->where('tipo', TipoLancamentoEnum::Saida)
            ->where('categoria', CategoriaLancamentoEnum::Reembolso)
            ->whereDate('data', '>=', $de->format('Y-m-d'))
            ->whereDate('data', '<=', $ate->format('Y-m-d'))
            ->sum('valor');
    }

    /**
     * Saldo do último dia do mês anterior ao período.
     */
    public function saldoAnterior(int $mes, int $ano): float
    {
        $ultimoDiaAnterior = Carbon::createFromDate($ano, $mes, 1)->subDay();

        return $this->saldoAcumulado($ultimoDiaAnterior);
    }
}
