<?php

use App\Services\DashboardService;
use App\Services\SaldoService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Dashboard')]
class extends Component {
    public string $dataInicio = '';
    public string $dataFim = '';
    public string $grafico = 'arrecadacao_mensal';

    public function mount(): void
    {
        $this->dataInicio = now()->startOfYear()->format('Y-m-d');
        $this->dataFim = now()->format('Y-m-d');
    }

    public function with(): array
    {
        $inicio = Carbon::parse($this->dataInicio)->startOfDay();
        $fim = Carbon::parse($this->dataFim)->endOfDay();

        if ($inicio->gt($fim)) {
            $inicio = $fim->copy()->subYear();
        }

        $saldoService = app(SaldoService::class);
        $dashboardService = app(DashboardService::class);

        $chartData = match ($this->grafico) {
            'arrecadacao_mensal' => $dashboardService->arrecadacaoPorMes($inicio, $fim),
            'saidas_mensal' => $dashboardService->saidasPorMes($inicio, $fim),
            'entradas_vs_saidas' => $dashboardService->entradasVsSaidasPorMes($inicio, $fim),
            'por_segmento' => $dashboardService->arrecadacaoPorSegmento($inicio, $fim),
            'por_categoria' => $dashboardService->saidasPorCategoria($inicio, $fim),
            'evolucao_saldo' => $dashboardService->evolucaoSaldoPorMes($inicio, $fim),
            default => $dashboardService->arrecadacaoPorMes($inicio, $fim),
        };

        return [
            'totalEntradas' => $saldoService->totalEntradasPeriodo($inicio, $fim),
            'totalSaidas' => $saldoService->totalSaidasPeriodo($inicio, $fim),
            'saldoAtual' => $saldoService->saldoAcumulado(),
            'saldoPeriodo' => $saldoService->saldoPeriodo($inicio, $fim),
            'ultimosLancamentos' => \App\Models\Lancamento::with(['user', 'segmentos'])
                ->whereDate('data', '>=', $inicio)
                ->whereDate('data', '<=', $fim)
                ->orderByDesc('data')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
            'chartData' => $chartData,
            'chartType' => $this->grafico,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Dashboard</h1>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">De</label>
                <input type="date" wire:model.live="dataInicio" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-700">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Até</label>
                <input type="date" wire:model.live="dataFim" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-700">
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Gráfico</label>
                <select wire:model.live="grafico" class="rounded border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-700">
                    <option value="arrecadacao_mensal">Arrecadação por mês</option>
                    <option value="saidas_mensal">Saídas por mês</option>
                    <option value="entradas_vs_saidas">Entradas vs Saídas</option>
                    <option value="por_segmento">Arrecadação por segmento</option>
                    <option value="por_categoria">Saídas por categoria</option>
                    <option value="evolucao_saldo">Evolução do saldo</option>
                </select>
            </div>
        </div>
    </div>

    <div class="grid auto-rows-min gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Entradas no período</h3>
            <p class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                R$ {{ number_format($totalEntradas, 2, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saídas no período</h3>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                R$ {{ number_format($totalSaidas, 2, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo do período</h3>
            <p class="mt-2 text-2xl font-semibold {{ $saldoPeriodo >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($saldoPeriodo, 2, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo acumulado</h3>
            <p class="mt-2 text-2xl font-semibold {{ $saldoAtual >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($saldoAtual, 2, ',', '.') }}
            </p>
        </div>
    </div>

    @if(!empty($chartData['labels']))
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800"
        wire:key="chart-{{ $dataInicio }}-{{ $dataFim }}-{{ $grafico }}">
        <h3 class="mb-4 text-lg font-semibold">
            @switch($chartType)
                @case('arrecadacao_mensal') Arrecadação por mês @break
                @case('saidas_mensal') Saídas por mês @break
                @case('entradas_vs_saidas') Entradas vs Saídas @break
                @case('por_segmento') Arrecadação por segmento @break
                @case('por_categoria') Saídas por categoria @break
                @case('evolucao_saldo') Evolução do saldo @break
                @default Gráfico
            @endswitch
        </h3>
        <div class="h-80" data-chart-config="{{ json_encode($chartData) }}" data-chart-type="{{ $chartType }}"
            x-data="dashboardChart()" x-init="init()">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
        <p class="text-zinc-500">Nenhum dado para exibir no gráfico no período selecionado.</p>
    </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <h3 class="mb-4 text-lg font-semibold">Lançamentos no período</h3>
        @if($ultimosLancamentos->isEmpty())
            <p class="text-zinc-500">Nenhum lançamento no período selecionado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Data</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Tipo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Categoria</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Descrição</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($ultimosLancamentos as $lancamento)
                            <tr>
                                <td class="px-4 py-2">{{ $lancamento->data->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $lancamento->tipo->value === 'entrada' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                        {{ ucfirst($lancamento->tipo->value) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ ucfirst($lancamento->categoria->value) }}</td>
                                <td class="px-4 py-2">{{ $lancamento->descricao }}</td>
                                <td class="px-4 py-2 font-medium {{ $lancamento->tipo->value === 'entrada' ? 'text-green-600' : 'text-red-600' }}">
                                    R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardChart', () => ({
        chart: null,
        init() {
            this.$nextTick(() => {
                const el = this.$el;
                const config = JSON.parse(el.dataset.chartConfig || '{}');
                const type = el.dataset.chartType || 'arrecadacao_mensal';
                const ctx = this.$refs.canvas.getContext('2d');
                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#a1a1aa' : '#71717a';
                const gridColor = isDark ? '#3f3f46' : '#e4e4e7';

                let chartConfig = {};
                if (type === 'entradas_vs_saidas') {
                    chartConfig = {
                        type: 'bar',
                        data: {
                            labels: config.labels,
                            datasets: [
                                { label: 'Entradas', data: config.entradas, backgroundColor: 'rgba(34, 197, 94, 0.7)', borderColor: 'rgb(34, 197, 94)' },
                                { label: 'Saídas', data: config.saidas, backgroundColor: 'rgba(239, 68, 68, 0.7)', borderColor: 'rgb(239, 68, 68)' }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } }, scales: { x: { ticks: { color: textColor }, grid: { color: gridColor } }, y: { ticks: { color: textColor }, grid: { color: gridColor } } } }
                    };
                } else if (type === 'por_segmento' || type === 'por_categoria') {
                    chartConfig = {
                        type: 'doughnut',
                        data: {
                            labels: config.labels,
                            datasets: [{ data: config.data, backgroundColor: ['rgba(34, 197, 94, 0.8)', 'rgba(59, 130, 246, 0.8)', 'rgba(251, 191, 36, 0.8)', 'rgba(168, 85, 247, 0.8)', 'rgba(236, 72, 153, 0.8)', 'rgba(20, 184, 166, 0.8)'] }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } } }
                    };
                } else {
                    const color = type === 'saidas_mensal' ? 'rgba(239, 68, 68, 0.7)' : 'rgba(34, 197, 94, 0.7)';
                    const borderColor = type === 'saidas_mensal' ? 'rgb(239, 68, 68)' : 'rgb(34, 197, 94)';
                    chartConfig = {
                        type: 'bar',
                        data: { labels: config.labels, datasets: [{ label: type === 'evolucao_saldo' ? 'Saldo' : 'Valor', data: config.data, backgroundColor: color, borderColor: borderColor }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor } } }, scales: { x: { ticks: { color: textColor }, grid: { color: gridColor } }, y: { ticks: { color: textColor }, grid: { color: gridColor } } } }
                    };
                }
                this.chart = new Chart(ctx, chartConfig);
            });
        }
    }));
});
</script>
