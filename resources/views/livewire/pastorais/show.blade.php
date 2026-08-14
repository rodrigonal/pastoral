<?php

use App\Models\Pastoral;
use App\Models\PastoralImagem;
use App\Support\FrasesPastorais;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')]
#[Title('Fotos da pastoral')]
class extends Component {
    use WithFileUploads;

    public Pastoral $pastoral;

    public string $data = '';
    public string $titulo = '';
    public string $agradecimento = '';
    public ?int $frase_id = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $fotos = [];

    public function mount(Pastoral $pastoral): void
    {
        $this->pastoral = $pastoral;
        $this->data = $pastoral->data->format('Y-m-d');
        $this->titulo = (string) $pastoral->titulo;
        $this->agradecimento = (string) $pastoral->agradecimento;
        $this->frase_id = $pastoral->frase_id;
    }

    public function save(): void
    {
        $this->authorize('pastorais.update');

        $this->validate([
            'data' => ['required', 'date'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'agradecimento' => ['nullable', 'string'],
            'frase_id' => ['nullable', 'integer'],
        ]);

        $this->pastoral->update([
            'data' => $this->data,
            'titulo' => $this->titulo !== '' ? $this->titulo : null,
            'agradecimento' => $this->agradecimento,
            'frase_id' => $this->frase_id,
        ]);

        session()->flash('message', 'Dados da pastoral atualizados.');
    }

    public function uploadFotos(): void
    {
        $this->authorize('pastorais.update');

        $this->validate([
            'fotos' => ['required', 'array', 'min:1', 'max:12'],
            'fotos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $ordem = (int) $this->pastoral->imagens()->max('ordem');

        foreach ($this->fotos as $foto) {
            $ordem++;
            $path = $foto->store('pastorais/'.$this->pastoral->id, 'public');
            PastoralImagem::create([
                'pastoral_id' => $this->pastoral->id,
                'path' => $path,
                'ordem' => $ordem,
            ]);
        }

        $this->fotos = [];
        $this->pastoral->refresh();
        session()->flash('message', 'Fotos enviadas.');
    }

    public function removerFoto(int $id): void
    {
        $this->authorize('pastorais.update');

        $imagem = $this->pastoral->imagens()->where('id', $id)->firstOrFail();
        Storage::disk('public')->delete($imagem->path);
        $imagem->delete();
        $this->pastoral->refresh();
        session()->flash('message', 'Foto removida.');
    }

    public function with(): array
    {
        return [
            'imagens' => $this->pastoral->imagens()->get(),
            'frases' => FrasesPastorais::todas(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('pastorais.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
            <h1 class="text-xl font-semibold">{{ $pastoral->tituloExibicao() }}</h1>
        </div>
        <a href="{{ route('pastorais.arte', $pastoral) }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Gerar arte
        </a>
    </div>

    @if (session('message'))
        <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ session('message') }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 font-medium">Dados</h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Data *</label>
                    <input type="date" wire:model="data" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Título</label>
                    <input type="text" wire:model="titulo" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Agradecimento</label>
                    <textarea wire:model="agradecimento" rows="4" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700"></textarea>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Frase da arte</label>
                    <select wire:model="frase_id" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                        @foreach($frases as $frase)
                            <option value="{{ $frase['id'] }}">{{ $frase['autor'] }} — {{ \Illuminate\Support\Str::limit($frase['texto'], 70) }}</option>
                        @endforeach
                    </select>
                </div>
                @can('pastorais.update')
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">Salvar</button>
                @endcan
            </form>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 font-medium">Fotos</h2>
            @can('pastorais.update')
            <form wire:submit="uploadFotos" class="mb-6 space-y-3">
                <input type="file" wire:model="fotos" multiple accept="image/jpeg,image/png,image/webp" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700">
                @error('fotos.*') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                @error('fotos') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                <div wire:loading wire:target="fotos" class="text-sm text-zinc-500">Carregando arquivos…</div>
                <button type="submit" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">Enviar fotos</button>
            </form>
            @endcan

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                @forelse($imagens as $imagem)
                    <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600">
                        <img src="{{ $imagem->url() }}" alt="" class="h-32 w-full object-cover">
                        @can('pastorais.update')
                        <button type="button" wire:click="removerFoto({{ $imagem->id }})" wire:confirm="Remover esta foto?" class="absolute right-1 top-1 rounded bg-black/60 px-2 py-0.5 text-xs text-white">×</button>
                        @endcan
                    </div>
                @empty
                    <p class="col-span-full text-sm text-zinc-500">Nenhuma foto ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
