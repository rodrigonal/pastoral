<?php

use App\Models\Benfeitor;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Benfeitores')]
class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'benfeitores' => Benfeitor::query()
                ->withCount(['lancamentos as doacoes_count' => fn ($q) => $q->contaAtual()->where('tipo', 'entrada')])
                ->withSum(['lancamentos as total_doado' => fn ($q) => $q->contaAtual()->where('tipo', 'entrada')], 'valor')
                ->orderBy('nome')
                ->paginate(20),
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize('benfeitores.delete');

        $benfeitor = Benfeitor::findOrFail($id);

        if ($benfeitor->lancamentos()->exists()) {
            session()->flash('error', 'Não é possível excluir um benfeitor com lançamentos. Desative-o na edição.');

            return;
        }

        $benfeitor->delete();
        session()->flash('message', 'Benfeitor excluído com sucesso.');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Benfeitores</h1>
        @can('benfeitores.create')
        <a href="{{ route('benfeitores.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Novo Benfeitor
        </a>
        @endcan
    </div>

    @if (session('message'))
        <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ session('message') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-400">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Doações</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Total</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($benfeitores as $benfeitor)
                        <tr>
                            <td class="px-4 py-2">{{ $benfeitor->nome }}</td>
                            <td class="px-4 py-2">{{ $benfeitor->doacoes_count }}</td>
                            <td class="px-4 py-2">R$ {{ number_format((float) $benfeitor->total_doado, 2, ',', '.') }}</td>
                            <td class="px-4 py-2">
                                @if($benfeitor->ativo)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">Ativo</span>
                                @else
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @can('benfeitores.update')
                                <a href="{{ route('benfeitores.edit', $benfeitor) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Editar</a>
                                @else
                                -
                                @endcan
                                @can('benfeitores.delete')
                                    <span class="text-zinc-400">|</span>
                                    <button wire:click="delete({{ $benfeitor->id }})" wire:confirm="Excluir este benfeitor?" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Excluir</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Nenhum benfeitor cadastrado. Cadastre ao classificar as entradas do extrato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 px-4 pb-4">
            {{ $benfeitores->links() }}
        </div>
    </div>
</div>
