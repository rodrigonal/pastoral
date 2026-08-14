<?php

use App\Models\Pastoral;
use App\Support\FrasesPastorais;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Arte da pastoral')]
class extends Component {
    public Pastoral $pastoral;

    public string $agradecimento = '';
    public ?int $frase_id = null;
    public array $fotosSelecionadas = [];

    public function mount(Pastoral $pastoral): void
    {
        $this->pastoral = $pastoral->load('imagens');
        $this->agradecimento = $pastoral->agradecimento ?: FrasesPastorais::agradecimentoPadrao();
        $this->frase_id = $pastoral->frase_id ?: FrasesPastorais::aleatoria()['id'];
        $this->fotosSelecionadas = $this->pastoral->imagens->take(4)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function fraseAleatoria(): void
    {
        $this->frase_id = FrasesPastorais::aleatoria()['id'];
    }

    public function with(): array
    {
        $frase = $this->frase_id ? FrasesPastorais::find((int) $this->frase_id) : FrasesPastorais::aleatoria();
        $ids = array_map('intval', $this->fotosSelecionadas);
        $fotos = $this->pastoral->imagens->whereIn('id', $ids)->values();

        return [
            'frase' => $frase,
            'frases' => FrasesPastorais::todas(),
            'fotos' => $fotos,
            'todasFotos' => $this->pastoral->imagens,
            'mesNome' => $this->pastoral->data->locale('pt_BR')->translatedFormat('F \d\e Y'),
            'dataFormatada' => $this->pastoral->data->format('d/m/Y'),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('pastorais.show', $pastoral) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
            <h1 class="text-xl font-semibold">Arte / prova social</h1>
        </div>
        <button type="button" onclick="exportarArte()" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Baixar imagem
        </button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[340px_1fr]">
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <label class="mb-1 block text-sm font-medium">Frase</label>
                <select wire:model.live="frase_id" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700">
                    @foreach($frases as $item)
                        <option value="{{ $item['id'] }}">{{ $item['autor'] }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="fraseAleatoria" class="mt-2 text-sm text-zinc-600 underline dark:text-zinc-400">Sortear frase</button>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Agradecimento</label>
                <textarea wire:model.live="agradecimento" rows="5" class="w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700"></textarea>
            </div>
            <div>
                <p class="mb-2 text-sm font-medium">Fotos na arte (até 4)</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($todasFotos as $imagem)
                        <label class="block cursor-pointer">
                            <input type="checkbox" value="{{ $imagem->id }}" wire:model.live="fotosSelecionadas" class="mb-1">
                            <img src="{{ $imagem->url() }}" alt="" class="h-20 w-full rounded object-cover">
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-auto rounded-xl border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div id="arte-pastoral" class="mx-auto w-[540px] overflow-hidden rounded-sm" style="background:#4a2e22;color:#f7f0e6;font-family:'Georgia',serif;">
                <div class="px-8 pt-8 text-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Fraternidade O Caminho" class="mx-auto mb-4 h-16 object-contain">
                    <p class="text-[11px] uppercase tracking-[0.25em]" style="color:#d4b896;">Fraternidade O Caminho · PJC</p>
                    <h2 class="mt-2 text-2xl font-semibold leading-tight">Pastoral de Rua</h2>
                    <p class="mt-1 text-sm" style="color:#e8d5b5;">{{ $mesNome }} · {{ $dataFormatada }}</p>
                </div>

                @if($fotos->isNotEmpty())
                    <div class="mt-6 grid grid-cols-2 gap-1 px-4">
                        @foreach($fotos->take(4) as $foto)
                            <img src="{{ $foto->url() }}" alt="" class="h-40 w-full object-cover" style="{{ $fotos->count() === 1 ? 'grid-column: span 2; height: 280px;' : '' }}">
                        @endforeach
                    </div>
                @endif

                <div class="px-8 py-8 text-center">
                    @if($frase)
                        <p class="text-lg leading-snug italic">“{{ $frase['texto'] }}”</p>
                        <p class="mt-3 text-xs uppercase tracking-widest" style="color:#d4b896;">{{ $frase['autor'] }}</p>
                    @endif
                    <p class="mt-6 text-sm leading-relaxed" style="color:#f3e6d4;">{{ $agradecimento }}</p>
                    <p class="mt-8 text-[11px] uppercase tracking-[0.2em]" style="color:#d4b896;">Obrigado por caminhar conosco</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
function exportarArte() {
    const el = document.getElementById('arte-pastoral');
    if (!el || typeof html2canvas === 'undefined') {
        return;
    }
    html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#4a2e22' }).then((canvas) => {
        const link = document.createElement('a');
        link.download = 'pastoral-de-rua-arte.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}
</script>
