<?php

use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\Segmento;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Lançamentos')]
class extends Component {
    use WithPagination;

    public string $filtroDataInicio = '';
    public string $filtroDataFim = '';
    public string $filtroTipo = '';
    public string $filtroCategoria = '';
    public ?int $filtroSegmentoId = null;

    public function mount(): void
    {
        $this->filtroDataInicio = now()->startOfMonth()->format('Y-m-d');
        $this->filtroDataFim = now()->endOfMonth()->format('Y-m-d');
    }

    public function with(): array
    {
        $query = Lancamento::with(['user', 'segmentos'])->orderByDesc('data')->orderByDesc('id');

        if ($this->filtroDataInicio) {
            $query->whereDate('data', '>=', $this->filtroDataInicio);
        }
        if ($this->filtroDataFim) {
            $query->whereDate('data', '<=', $this->filtroDataFim);
        }
        if ($this->filtroTipo) {
            $query->where('tipo', $this->filtroTipo);
        }
        if ($this->filtroCategoria) {
            $query->where('categoria', $this->filtroCategoria);
        }
        if ($this->filtroSegmentoId) {
            $query->whereHas('segmentos', fn ($q) => $q->where('segmentos.id', $this->filtroSegmentoId));
        }

        return [
            'lancamentos' => $query->paginate(15),
            'segmentos' => Segmento::where('ativo', true)->orderBy('ordem')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Lançamentos</h1>
        @can('lancamentos.create')
        <a href="{{ route('lancamentos.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Novo Lançamento
        </a>
        @endcan
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex flex-wrap gap-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Data Início</label>
                <input type="date" wire:model.live="filtroDataInicio" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Data Fim</label>
                <input type="date" wire:model.live="filtroDataFim" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Tipo</label>
                <select wire:model.live="filtroTipo" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
                    <option value="">Todos</option>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Categoria</label>
                <select wire:model.live="filtroCategoria" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
                    <option value="">Todas</option>
                    @foreach(CategoriaLancamentoEnum::cases() as $cat)
                        <option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Segmento</label>
                <select wire:model.live="filtroSegmentoId" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
                    <option value="">Todos</option>
                    @foreach($segmentos as $seg)
                        <option value="{{ $seg->id }}">{{ $seg->nome }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Categoria</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Descrição</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Segmento</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Valor</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500">Anexo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($lancamentos as $lancamento)
                        <tr>
                            <td class="px-4 py-2">{{ $lancamento->data->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $lancamento->tipo->value === 'entrada' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ ucfirst($lancamento->tipo->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-2">{{ ucfirst($lancamento->categoria->value) }}</td>
                            <td class="px-4 py-2">{{ $lancamento->descricao }}</td>
                            <td class="px-4 py-2">{{ $lancamento->segmentos->pluck('nome')->implode(', ') ?: '-' }}</td>
                            <td class="px-4 py-2 font-medium {{ $lancamento->tipo->value === 'entrada' ? 'text-green-600' : 'text-red-600' }}">
                                R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($lancamento->anexo_path)
                                    <a href="{{ route('lancamentos.anexo', $lancamento) }}{{ in_array(strtolower(pathinfo($lancamento->anexo_path, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif']) ? '?inline=1' : '' }}" target="_blank" class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700" title="{{ basename($lancamento->anexo_path) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        Ver
                                    </a>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @can('lancamentos.update')
                                <a href="{{ route('lancamentos.edit', $lancamento) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Editar</a>
                                @else
                                -
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-zinc-500">Nenhum lançamento encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $lancamentos->links() }}
        </div>
    </div>
</div>
