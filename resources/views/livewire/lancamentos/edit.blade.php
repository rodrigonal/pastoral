<?php

use App\Actions\Lancamento\UpdateLancamentoAction;
use App\Enums\CategoriaLancamentoEnum;
use App\Enums\TipoLancamentoEnum;
use App\Models\Benfeitor;
use App\Models\Lancamento;
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
    public $anexos = [];
    public string $benfeitor_id = '';
    public string $novo_benfeitor_nome = '';
    public bool $classificado = true;

    public function mount(Lancamento $lancamento): void
    {
        $this->lancamento = $lancamento->load('anexos');
        $this->data = $lancamento->data->format('Y-m-d');
        $this->tipo = $lancamento->tipo->value;
        $this->categoria = $lancamento->categoria->value;
        $this->valor = number_format($lancamento->valor, 2, ',', '');
        $this->descricao = $lancamento->descricao;
        $this->observacao = $lancamento->observacao;
        $this->benfeitor_id = $lancamento->benfeitor_id ? (string) $lancamento->benfeitor_id : '';
        $this->classificado = (bool) $lancamento->classificado;
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
            'anexos' => ['nullable', 'array', 'max:10'],
            'anexos.*' => ['file', 'mimes:pdf,jpeg,jpg,png', 'max:5120'],
        ]);

        $data = [
            'data' => $this->data,
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'valor' => (float) str_replace(',', '.', str_replace('.', '', preg_replace('/R\$\s*/', '', $this->valor))),
            'descricao' => $this->descricao,
            'observacao' => $this->observacao,
            'anexos' => $this->anexos,
            'benfeitor_id' => $this->benfeitor_id !== '' && $this->benfeitor_id !== 'novo' ? (int) $this->benfeitor_id : null,
            'novo_benfeitor_nome' => $this->benfeitor_id === 'novo' ? $this->novo_benfeitor_nome : null,
            'classificado' => $this->classificado,
        ];

        app(UpdateLancamentoAction::class)->execute($this->lancamento, $data);

        session()->flash('message', 'Lançamento atualizado com sucesso.');
        $this->redirect(route('lancamentos.index'), navigate: true);
    }

    public function removeAnexo(int $anexoId): void
    {
        $this->authorize('lancamentos.update');

        $anexo = $this->lancamento->anexos()->findOrFail($anexoId);
        $anexo->excluirArquivo();
        $this->lancamento->load('anexos');

        session()->flash('message', 'Anexo removido com sucesso.');
    }

    public function with(): array
    {
        return [
            'benfeitores' => Benfeitor::where('ativo', true)->orderBy('nome')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('lancamentos.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
        <h1 class="text-xl font-semibold">Editar Lançamento</h1>
    </div>

    @if($lancamento->is_historico)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">
            Este lançamento pertence à conta antiga e entra apenas como histórico. Ele não altera o saldo da conta atual.
        </div>
    @elseif(! $lancamento->classificado)
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300">
            Lançamento importado do extrato e ainda não classificado. Ajuste o título, a descrição
            @if($categoria === 'arrecadacao') e o benfeitor @endif
            e marque como classificado.
        </div>
    @endif

    @if($lancamento->historico_bancario)
        <p class="text-sm text-zinc-500">Extrato: {{ $lancamento->historico_bancario }}@if($lancamento->documento) · Doc. {{ $lancamento->documento }}@endif</p>
    @endif

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
                        <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
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
                    <label class="mb-1 block text-sm font-medium">Benfeitor {{ $classificado ? '*' : '' }}</label>
                    <select wire:model.live="benfeitor_id" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                        <option value="">Não identificado</option>
                        @foreach($benfeitores as $benfeitor)
                            <option value="{{ $benfeitor->id }}">{{ $benfeitor->nome }}</option>
                        @endforeach
                        <option value="novo">+ Cadastrar novo benfeitor</option>
                    </select>
                    @error('benfeitor_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
                @if($benfeitor_id === 'novo')
                    <div>
                        <label class="mb-1 block text-sm font-medium">Nome do benfeitor *</label>
                        <input type="text" wire:model="novo_benfeitor_nome" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" placeholder="Nome completo">
                        @error('novo_benfeitor_nome') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                @endif
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium">Valor *</label>
                <x-currency-input model="valor" placeholder="R$ 0,00" required />
                @error('valor') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Título *</label>
                <input type="text" wire:model="descricao" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('descricao') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Descrição</label>
                <textarea wire:model="observacao" rows="3" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="classificado" wire:model="classificado" class="rounded border-zinc-300 dark:border-zinc-600">
                <label for="classificado" class="text-sm font-medium">Classificado</label>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Anexos (PDF ou imagem)</label>
                <input type="file" wire:model="anexos" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                @if(is_array($anexos) && count($anexos) > 0)
                    <p class="mt-1 text-xs text-zinc-500">Novos arquivos serão adicionados aos já existentes ao salvar.</p>
                    <ul class="mt-2 list-inside list-disc text-sm text-zinc-600 dark:text-zinc-400">
                        @foreach($anexos as $arquivo)
                            <li>{{ is_object($arquivo) ? $arquivo->getClientOriginalName() : $arquivo }}</li>
                        @endforeach
                    </ul>
                @endif
                @if($lancamento->anexos->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($lancamento->anexos as $anexo)
                            @php $anexoUrl = route('lancamentos.anexo', $anexo) . ($anexo->ehImagem() ? '?inline=1' : ''); @endphp
                            <div class="flex flex-wrap items-start gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-900/50">
                                @if($anexo->ehImagem())
                                    <a href="{{ $anexoUrl }}" target="_blank" class="block shrink-0">
                                        <img src="{{ $anexoUrl }}" alt="Preview" class="h-20 w-20 rounded border border-zinc-300 object-cover dark:border-zinc-600">
                                    </a>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $anexo->nome() }}</p>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        <a href="{{ $anexoUrl }}" target="_blank" class="text-sm text-zinc-600 underline hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                            {{ $anexo->ehImagem() ? 'Ver imagem' : 'Baixar PDF' }}
                                        </a>
                                        @can('lancamentos.update')
                                        <button type="button" wire:click="removeAnexo({{ $anexo->id }})" wire:confirm="Remover este anexo? O arquivo será excluído permanentemente."
                                            class="text-sm text-red-600 underline hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            Remover
                                        </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @error('anexos') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                @error('anexos.*') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
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
