<?php

use App\Models\Pastoral;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Pastoral de Rua')]
class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'pastorais' => Pastoral::query()
                ->withCount('imagens')
                ->orderByDesc('data')
                ->paginate(15),
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize('pastorais.delete');

        $pastoral = Pastoral::with('imagens')->findOrFail($id);

        foreach ($pastoral->imagens as $imagem) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($imagem->path);
        }

        $pastoral->delete();
        session()->flash('message', 'Registro da pastoral excluído.');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold">Pastoral de Rua</h1>
            <p class="mt-1 text-sm text-zinc-500">Fotos da ação e arte de agradecimento para redes sociais.</p>
        </div>
        @can('pastorais.create')
        <a href="{{ route('pastorais.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Nova pastoral
        </a>
        @endcan
    </div>

    @if (session('message'))
        <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ session('message') }}</div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Título</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Fotos</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($pastorais as $pastoral)
                        <tr>
                            <td class="px-4 py-2">{{ $pastoral->data->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">{{ $pastoral->tituloExibicao() }}</td>
                            <td class="px-4 py-2">{{ $pastoral->imagens_count }}</td>
                            <td class="px-4 py-2">
                                <a href="{{ route('pastorais.show', $pastoral) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Fotos</a>
                                <span class="text-zinc-400">|</span>
                                <a href="{{ route('pastorais.arte', $pastoral) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Arte</a>
                                @can('pastorais.delete')
                                    <span class="text-zinc-400">|</span>
                                    <button wire:click="delete({{ $pastoral->id }})" wire:confirm="Excluir esta pastoral e as fotos?" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Excluir</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-zinc-500">Nenhuma pastoral registrada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $pastorais->links() }}
        </div>
    </div>
</div>
