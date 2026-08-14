<?php

namespace App\Services;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\ControleSaldo;
use App\Models\Lancamento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
     * Lançamentos da conta antiga (CS), inacessível — só histórico.
     */
    public function queryLegado(): Builder
    {
        return Lancamento::query()->historico();
    }

    /**
     * Saldo acumulado da conta atual até determinada data (inclusive).
     * Reembolsos não afetam o saldo. O saldo legado da conta antiga é descontado.
     */
    public function saldoAcumulado(?Carbon $ate = null): float
    {
        return round($this->saldoLivro($ate) - $this->saldoLegado($ate), 2);
    }

    /**
     * Saldo do livro da conta antiga (Centro Social), que ficou inacessível.
     */
    public function saldoLegado(?Carbon $ate = null): float
    {
        return $this->saldoDaQuery($this->queryLegado(), $ate);
    }

    /**
     * Valor em espécie informado manualmente (não entra nos lançamentos).
     */
    public function saldoEmMaos(): float
    {
        return round((float) ControleSaldo::registro()->saldo_em_maos, 2);
    }

    /**
     * Saldo na conta bancária atual = livro caixa − saldo legado − saldo em mãos.
     */
    public function saldoEmConta(?Carbon $ate = null): float
    {
        return round($this->saldoAcumulado($ate) - $this->saldoEmMaos(), 2);
    }

    /**
     * Soma de todos os lançamentos (conta atual + legado), exceto reembolsos.
     */
    public function saldoLivro(?Carbon $ate = null): float
    {
        return $this->saldoDaQuery(Lancamento::query(), $ate);
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
