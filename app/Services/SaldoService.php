<?php

namespace App\Services;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use Carbon\Carbon;

class SaldoService
{
    /**
     * Saldo acumulado até determinada data (inclusive).
     * Reembolsos não afetam o saldo (apenas controle no relatório).
     */
    public function saldoAcumulado(?Carbon $ate = null): float
    {
        $query = Lancamento::query();

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

        return round($entradas - $saidas, 2);
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
        return (float) Lancamento::query()
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
        return (float) Lancamento::query()
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
        return (float) Lancamento::query()
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
