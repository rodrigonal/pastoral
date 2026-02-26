<?php

use App\Services\SaldoService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Dashboard')]
class extends Component {
    public function with(): array
    {
        $saldoService = app(SaldoService::class);
        $inicioMes = Carbon::now()->startOfMonth();
        $fimMes = Carbon::now()->endOfMonth();

        return [
            'totalEntradas' => $saldoService->totalEntradasPeriodo($inicioMes, $fimMes),
            'totalSaidas' => $saldoService->totalSaidasPeriodo($inicioMes, $fimMes),
            'saldoAtual' => $saldoService->saldoAcumulado(),
            'ultimosLancamentos' => \App\Models\Lancamento::with(['user', 'segmentos'])
                ->orderByDesc('data')
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Entradas do Mês</h3>
            <p class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                R$ {{ number_format($totalEntradas, 2, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saídas do Mês</h3>
            <p class="mt-2 text-2xl font-semibold text-red-600 dark:text-red-400">
                R$ {{ number_format($totalSaidas, 2, ',', '.') }}
            </p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo Atual</h3>
            <p class="mt-2 text-2xl font-semibold {{ $saldoAtual >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($saldoAtual, 2, ',', '.') }}
            </p>
        </div>
    </div>
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <h3 class="mb-4 text-lg font-semibold">Últimos Lançamentos</h3>
        @if($ultimosLancamentos->isEmpty())
            <p class="text-zinc-500">Nenhum lançamento cadastrado.</p>
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
