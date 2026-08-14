<?php

use App\Models\Pastoral;
use App\Support\FrasesPastorais;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Nova pastoral')]
class extends Component {
    public string $data = '';
    public string $titulo = '';
    public string $agradecimento = '';

    public function mount(): void
    {
        $this->data = now()->format('Y-m-d');
        $this->agradecimento = FrasesPastorais::agradecimentoPadrao();
        $this->titulo = 'Pastoral de Rua · '.now()->locale('pt_BR')->translatedFormat('F \d\e Y');
    }

    public function save(): void
    {
        $this->authorize('pastorais.create');

        $this->validate([
            'data' => ['required', 'date'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'agradecimento' => ['nullable', 'string'],
        ]);

        $pastoral = Pastoral::create([
            'data' => $this->data,
            'titulo' => $this->titulo !== '' ? $this->titulo : null,
            'agradecimento' => $this->agradecimento !== '' ? $this->agradecimento : FrasesPastorais::agradecimentoPadrao(),
            'frase_id' => FrasesPastorais::aleatoria()['id'],
            'user_id' => auth()->id(),
        ]);

        session()->flash('message', 'Pastoral criada. Agora envie as fotos.');
        $this->redirect(route('pastorais.show', $pastoral), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('pastorais.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
        <h1 class="text-xl font-semibold">Nova pastoral</h1>
    </div>

    <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Data da pastoral *</label>
                <input type="date" wire:model="data" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('data') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Título</label>
                <input type="text" wire:model="titulo" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Agradecimento</label>
                <textarea wire:model="agradecimento" rows="4" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700"></textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Continuar
                </button>
                <a href="{{ route('pastorais.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">Cancelar</a>
            </div>
        </form>
    </div>
</div>
