<?php

use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Benfeitor')]
class extends Component {
    public Benfeitor $benfeitor;

    public function mount(Benfeitor $benfeitor): void
    {
        $this->benfeitor = $benfeitor->load('membro');
    }

    public function with(): array
    {
        $doacoes = $this->benfeitor->lancamentos()
            ->contaAtual()
            ->where('tipo', TipoLancamentoEnum::Entrada)
            ->orderByDesc('data')
            ->get();

        return [
            'doacoes' => $doacoes,
            'total' => (float) $doacoes->sum('valor'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('benfeitores.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
            <div>
                <h1 class="text-xl font-semibold">{{ $benfeitor->nome }}</h1>
                <p class="text-sm text-zinc-500">Ligado a {{ $benfeitor->membro?->name ?: 'nenhum membro' }}</p>
            </div>
        </div>
        @can('benfeitores.update')
        <a href="{{ route('benfeitores.edit', $benfeitor) }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">Editar cadastro</a>
        @endcan
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-3 text-sm font-medium text-zinc-500">Doações na conta atual</h2>
        <p class="mb-4 text-2xl font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($total, 2, ',', '.') }}</p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Título</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($doacoes as $doacao)
                        <tr>
                            <td class="px-4 py-2">{{ $doacao->data->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $doacao->descricao }}</td>
                            <td class="px-4 py-2 font-medium text-green-600">R$ {{ number_format($doacao->valor, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-zinc-500">Nenhuma doação classificada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
