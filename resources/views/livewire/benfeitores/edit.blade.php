<?php

use App\Models\Benfeitor;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Editar Benfeitor')]
class extends Component {
    public Benfeitor $benfeitor;

    public string $nome = '';
    public string $membro_id = '';
    public ?string $observacao = null;
    public bool $ativo = true;

    public function mount(Benfeitor $benfeitor): void
    {
        $this->benfeitor = $benfeitor;
        $this->nome = $benfeitor->nome;
        $this->membro_id = $benfeitor->membro_id ? (string) $benfeitor->membro_id : '';
        $this->observacao = $benfeitor->observacao;
        $this->ativo = (bool) $benfeitor->ativo;
    }

    public function save(): void
    {
        $this->authorize('benfeitores.update');

        $this->validate([
            'nome' => ['required', 'string', 'max:255', 'unique:benfeitores,nome,'.$this->benfeitor->id],
            'membro_id' => ['required', 'exists:users,id'],
            'observacao' => ['nullable', 'string'],
            'ativo' => ['boolean'],
        ]);

        $this->benfeitor->update([
            'nome' => trim($this->nome),
            'membro_id' => (int) $this->membro_id,
            'observacao' => $this->observacao,
            'ativo' => $this->ativo,
        ]);

        session()->flash('message', 'Benfeitor atualizado com sucesso.');
        $this->redirect(route('benfeitores.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'membros' => User::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('benfeitores.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
        <h1 class="text-xl font-semibold">Editar Benfeitor</h1>
    </div>

    <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Nome *</label>
                <input type="text" wire:model="nome" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('nome') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Membro da comunidade *</label>
                <select wire:model="membro_id" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                    <option value="">Selecione</option>
                    @foreach($membros as $membro)
                        <option value="{{ $membro->id }}">{{ $membro->name }}</option>
                    @endforeach
                </select>
                @error('membro_id') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Observação</label>
                <textarea wire:model="observacao" rows="3" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700"></textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="ativo" wire:model="ativo" class="rounded border-zinc-300 dark:border-zinc-600">
                <label for="ativo" class="text-sm font-medium">Ativo</label>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Salvar
                </button>
                <a href="{{ route('benfeitores.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">Cancelar</a>
            </div>
        </form>
    </div>
</div>
