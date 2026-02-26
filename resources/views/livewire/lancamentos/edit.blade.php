<?php

use App\Actions\Lancamento\UpdateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Lancamento;
use App\Models\Segmento;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')]
#[Title('Editar Lançamento')]
class extends Component {
    use WithFileUploads;

    public Lancamento $lancamento;

    public string $data = '';
    public string $tipo = '';
    public string $categoria = '';
    public string $valor = '';
    public string $descricao = '';
    public ?string $observacao = null;
    public $anexo = null;
    public array $segmento_ids = [];

    public function mount(Lancamento $lancamento): void
    {
        $this->lancamento = $lancamento;
        $this->data = $lancamento->data->format('Y-m-d');
        $this->tipo = $lancamento->tipo->value;
        $this->categoria = $lancamento->categoria->value;
        $this->valor = number_format($lancamento->valor, 2, ',', '');
        $this->descricao = $lancamento->descricao;
        $this->observacao = $lancamento->observacao;
        $this->segmento_ids = $lancamento->segmentos->pluck('id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function updatedCategoria($value): void
    {
        if ($value === CategoriaLancamentoEnum::Arrecadacao->value) {
            $this->tipo = TipoLancamentoEnum::Entrada->value;
        }
        if (in_array($value, [CategoriaLancamentoEnum::Repasse->value, CategoriaLancamentoEnum::Compra->value, CategoriaLancamentoEnum::Reembolso->value])) {
            $this->tipo = TipoLancamentoEnum::Saida->value;
        }
    }

    public function save(): void
    {
        $this->authorize('lancamentos.update');

        $this->validate([
            'anexo' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
        ]);

        $anexoPath = $this->lancamento->anexo_path;
        if ($this->anexo) {
            if ($this->lancamento->anexo_path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($this->lancamento->anexo_path);
            }
            $anexoPath = $this->anexo->store('lancamentos', 'local');
        }

        $data = [
            'data' => $this->data,
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'valor' => (float) str_replace(',', '.', str_replace('.', '', preg_replace('/R\$\s*/', '', $this->valor))),
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'anexo_path' => $anexoPath,
            'segmento_ids' => array_map('intval', array_filter($this->segmento_ids)),
        ];

        app(UpdateLancamentoAction::class)->execute($this->lancamento, $data);

        session()->flash('message', 'Lançamento atualizado com sucesso.');
        $this->redirect(route('lancamentos.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'segmentos' => Segmento::where('ativo', true)->orderBy('ordem')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('lancamentos.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
        <h1 class="text-xl font-semibold">Editar Lançamento</h1>
    </div>

    <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Data *</label>
                <input type="date" wire:model="data" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('data') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Categoria *</label>
                <select wire:model.live="categoria" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                    @foreach(CategoriaLancamentoEnum::cases() as $cat)
                        <option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Tipo *</label>
                <select wire:model="tipo" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                    <option value="entrada">Entrada</option>
                    <option value="saida">Saída</option>
                </select>
            </div>
            @if($categoria === 'arrecadacao')
                <div>
                    <label class="mb-1 block text-sm font-medium">Segmentos *</label>
                    <select wire:model="segmento_ids" multiple class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" size="5">
                        @foreach($segmentos as $seg)
                            <option value="{{ $seg->id }}">{{ $seg->nome }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-zinc-500">Segure Ctrl (ou Cmd) para selecionar múltiplos</p>
                    @error('segmento_ids') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium">Valor *</label>
                <x-currency-input model="valor" placeholder="R$ 0,00" required />
                @error('valor') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Descrição *</label>
                <input type="text" wire:model="descricao" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('descricao') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Observação</label>
                <textarea wire:model="observacao" rows="3" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Anexo (PDF ou imagem)</label>
                <input type="file" wire:model="anexo" accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                @if($lancamento->anexo_path)
                    @php
                        $ext = strtolower(pathinfo($lancamento->anexo_path, PATHINFO_EXTENSION));
                        $ehImagem = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
                    @endphp
                    <div class="mt-2 flex flex-wrap items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-900/50">
                        @if($ehImagem)
                            @php $anexoUrl = route('lancamentos.anexo', $lancamento) . '?inline=1'; @endphp
                            <a href="{{ $anexoUrl }}" target="_blank" class="block shrink-0">
                                <img src="{{ $anexoUrl }}" alt="Preview" class="h-20 w-20 rounded border border-zinc-300 object-cover dark:border-zinc-600">
                            </a>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ basename($lancamento->anexo_path) }}</p>
                            <div class="mt-1 flex gap-2">
                                <a href="{{ route('lancamentos.anexo', $lancamento) }}{{ $ehImagem ? '?inline=1' : '' }}" target="_blank" class="text-sm text-zinc-600 underline hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                    {{ $ehImagem ? 'Ver imagem' : 'Baixar PDF' }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
                @error('anexo') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Salvar
                </button>
                <a href="{{ route('lancamentos.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">Cancelar</a>
            </div>
        </form>
    </div>
</div>
