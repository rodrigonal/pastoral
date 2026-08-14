<?php

use App\Enums\CategoriaLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\Lancamento;
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
    public ?int $filtroBenfeitorId = null;
    public string $filtroStatus = 'conta_atual';

    public function mount(): void
    {
        $this->filtroDataInicio = now()->startOfYear()->format('Y-m-d');
        $this->filtroDataFim = now()->endOfMonth()->format('Y-m-d');
        $this->filtroStatus = request()->query('status', 'conta_atual');
    }

    public function with(): array
    {
        $query = Lancamento::with(['user', 'benfeitor', 'anexos'])->orderByDesc('data')->orderByDesc('id');

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
        if ($this->filtroBenfeitorId) {
            $query->where('benfeitor_id', $this->filtroBenfeitorId);
        }

        match ($this->filtroStatus) {
            'pendentes' => $query->pendentes(),
            'historico' => $query->historico(),
            'conta_atual' => $query->contaAtual(),
            default => $query,
        };

        return [
            'lancamentos' => $query->paginate(20),
            'benfeitores' => Benfeitor::where('ativo', true)->orderBy('nome')->get(),
            'pendentesCount' => Lancamento::pendentes()->count(),
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

    @if (session('message'))
        <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ session('message') }}</div>
    @endif

    @if($pendentesCount > 0 && $filtroStatus !== 'pendentes')
        <button type="button" wire:click="$set('filtroStatus', 'pendentes')" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-left text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">
            {{ $pendentesCount }} lançamento(s) pendente(s) de classificação — clique para filtrar.
        </button>
    @endif

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
                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Benfeitor</label>
                <select wire:model.live="filtroBenfeitorId" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
                    <option value="">Todos</option>
                    @foreach($benfeitores as $benfeitor)
                        <option value="{{ $benfeitor->id }}">{{ $benfeitor->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Origem</label>
                <select wire:model.live="filtroStatus" class="rounded border border-zinc-300 px-2 py-1 dark:border-zinc-600 dark:bg-zinc-700">
                    <option value="conta_atual">Conta atual</option>
                    <option value="pendentes">Pendentes de classificação</option>
                    <option value="historico">Histórico (conta antiga)</option>
                    <option value="todos">Todos</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Data</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Título</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Benfeitor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Valor</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-zinc-500">Anexo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($lancamentos as $lancamento)
                        <tr class="{{ ! $lancamento->classificado && ! $lancamento->is_historico ? 'bg-amber-50/60 dark:bg-amber-900/10' : '' }}">
                            <td class="px-4 py-2">{{ $lancamento->data->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $lancamento->tipo->value === 'entrada' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' }}">
                                    {{ ucfirst($lancamento->tipo->value) }}
                                </span>
                                @if(! $lancamento->classificado && ! $lancamento->is_historico)
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Pendente</span>
                                @endif
                                @if($lancamento->is_historico)
                                    <span class="ml-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Histórico</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ $lancamento->descricao }}</div>
                                @if($lancamento->observacao)
                                    <div class="mt-0.5 max-w-xs truncate text-xs text-zinc-500">{{ $lancamento->observacao }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $lancamento->benfeitor?->nome ?: '—' }}</td>
                            <td class="px-4 py-2 font-medium {{ $lancamento->tipo->value === 'entrada' ? 'text-green-600' : 'text-red-600' }}">
                                R$ {{ number_format($lancamento->valor, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($lancamento->anexos->isNotEmpty())
                                    <div class="flex flex-wrap items-center justify-center gap-1">
                                        @foreach($lancamento->anexos as $anexo)
                                            <a href="{{ route('lancamentos.anexo', $anexo) }}{{ $anexo->ehImagem() ? '?inline=1' : '' }}" target="_blank" class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700" title="{{ $anexo->nome() }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                                {{ $loop->count > 1 ? $loop->iteration : 'Ver' }}
                                            </a>
                                        @endforeach
                                    </div>
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
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500">Nenhum lançamento encontrado.</td>
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
