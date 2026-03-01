<?php

namespace App\Services;

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly SaldoService $saldoService
    ) {}

    /**
     * Arrecadação (entradas) por mês no período.
     */
    public function arrecadacaoPorMes(Carbon $inicio, Carbon $fim): array
    {
        $meses = [];
        $valores = [];
        $atual = $inicio->copy()->startOfMonth();

        while ($atual->lte($fim)) {
            $fimMes = $atual->copy()->endOfMonth();
            $de = $atual->copy();
            $ate = $fimMes->gt($fim) ? $fim->copy() : $fimMes;

            $total = (float) Lancamento::query()
                ->where('tipo', TipoLancamentoEnum::Entrada)
                ->whereDate('data', '>=', $de)
                ->whereDate('data', '<=', $ate)
                ->sum('valor');

            $meses[] = $atual->locale('pt_BR')->translatedFormat('M/y');
            $valores[] = round($total, 2);
            $atual->addMonth();
        }

        return ['labels' => $meses, 'data' => $valores];
    }

    /**
     * Saídas por mês no período (exclui reembolsos).
     */
    public function saidasPorMes(Carbon $inicio, Carbon $fim): array
    {
        $meses = [];
        $valores = [];
        $atual = $inicio->copy()->startOfMonth();

        while ($atual->lte($fim)) {
            $fimMes = $atual->copy()->endOfMonth();
            $de = $atual->copy();
            $ate = $fimMes->gt($fim) ? $fim->copy() : $fimMes;

            $total = $this->saldoService->totalSaidasPeriodo($de, $ate);

            $meses[] = $atual->locale('pt_BR')->translatedFormat('M/y');
            $valores[] = round($total, 2);
            $atual->addMonth();
        }

        return ['labels' => $meses, 'data' => $valores];
    }

    /**
     * Entradas vs Saídas por mês (comparativo).
     */
    public function entradasVsSaidasPorMes(Carbon $inicio, Carbon $fim): array
    {
        $entradas = $this->arrecadacaoPorMes($inicio, $fim);
        $saidas = $this->saidasPorMes($inicio, $fim);

        return [
            'labels' => $entradas['labels'],
            'entradas' => $entradas['data'],
            'saidas' => $saidas['data'],
        ];
    }

    /**
     * Arrecadação por segmento no período.
     */
    public function arrecadacaoPorSegmento(Carbon $inicio, Carbon $fim): array
    {
        $lancamentos = Lancamento::with('segmentos')
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->where('categoria', CategoriaLancamentoEnum::Arrecadacao)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->get();

        $porSegmento = [];
        foreach ($lancamentos as $lancamento) {
            foreach ($lancamento->segmentos as $segmento) {
                $porSegmento[$segmento->nome] = ($porSegmento[$segmento->nome] ?? 0) + (float) $lancamento->valor;
            }
        }

        $outros = (float) Lancamento::query()
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->whereDoesntHave('segmentos')
            ->sum('valor');

        if ($outros > 0) {
            $porSegmento['Sem segmento'] = $outros;
        }

        arsort($porSegmento);

        return [
            'labels' => array_keys($porSegmento),
            'data' => array_map(fn ($v) => round($v, 2), array_values($porSegmento)),
        ];
    }

    /**
     * Saídas por categoria no período (Repasse, Compra - exclui Reembolso).
     */
    public function saidasPorCategoria(Carbon $inicio, Carbon $fim): array
    {
        $porCategoria = Lancamento::query()
            ->where('tipo', TipoLancamentoEnum::Saida)
            ->where('categoria', '!=', CategoriaLancamentoEnum::Reembolso)
            ->whereDate('data', '>=', $inicio)
            ->whereDate('data', '<=', $fim)
            ->selectRaw('categoria, sum(valor) as total')
            ->groupBy('categoria')
            ->pluck('total', 'categoria')
            ->toArray();

        $labels = [];
        $data = [];
        foreach ($porCategoria as $categoria => $total) {
            $labels[] = ucfirst($categoria);
            $data[] = round((float) $total, 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Evolução do saldo mês a mês no período.
     */
    public function evolucaoSaldoPorMes(Carbon $inicio, Carbon $fim): array
    {
        $meses = [];
        $saldos = [];
        $saldoAcum = $this->saldoService->saldoAcumulado($inicio->copy()->subDay());
        $atual = $inicio->copy()->startOfMonth();

        while ($atual->lte($fim)) {
            $fimMes = $atual->copy()->endOfMonth();
            $de = $atual->copy();
            $ate = $fimMes->gt($fim) ? $fim->copy() : $fimMes;

            $entradas = $this->saldoService->totalEntradasPeriodo($de, $ate);
            $saidas = $this->saldoService->totalSaidasPeriodo($de, $ate);
            $saldoAcum += $entradas - $saidas;

            $meses[] = $atual->locale('pt_BR')->translatedFormat('M/y');
            $saldos[] = round($saldoAcum, 2);
            $atual->addMonth();
        }

        return ['labels' => $meses, 'data' => $saldos];
    }
}
