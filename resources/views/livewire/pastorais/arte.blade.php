<?php

use App\Models\Pastoral;
use App\Models\PastoralImagem;
use App\Services\PastoralArteGenerator;
use App\Support\FrasesPastorais;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function updatedFotosSelecionadas(): void
    {
        if (count($this->fotosSelecionadas) > 4) {
            $this->fotosSelecionadas = array_slice($this->fotosSelecionadas, -4);
        }
    }

    public function fraseAleatoria(): void
    {
        $this->frase_id = FrasesPastorais::aleatoria()['id'];
    }

    public function baixar(): StreamedResponse
    {
        $this->authorize('pastorais.view');

        $this->pastoral->update([
            'agradecimento' => $this->agradecimento,
            'frase_id' => $this->frase_id,
        ]);

        $fotos = $this->fotosEscolhidas();
        $frase = $this->frase_id ? FrasesPastorais::find((int) $this->frase_id) : FrasesPastorais::aleatoria();
        $png = app(PastoralArteGenerator::class)->gerar($this->pastoral, $fotos, $this->agradecimento, $frase);
        $nome = 'pastoral-de-rua-'.$this->pastoral->data->format('Y-m-d').'.png';

        return response()->streamDownload(function () use ($png) {
            echo $png;
        }, $nome, ['Content-Type' => 'image/png']);
    }

    public function with(): array
    {
        $frase = $this->frase_id ? FrasesPastorais::find((int) $this->frase_id) : FrasesPastorais::aleatoria();

        return [
            'frase' => $frase,
            'frases' => FrasesPastorais::todas(),
            'fotos' => $this->fotosEscolhidas(),
            'todasFotos' => $this->pastoral->imagens,
            'mesNome' => $this->pastoral->data->locale('pt_BR')->translatedFormat('F \d\e Y'),
            'dataFormatada' => $this->pastoral->data->format('d/m/Y'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, PastoralImagem>
     */
    private function fotosEscolhidas()
    {
        $ids = array_map('intval', $this->fotosSelecionadas);

        return $this->pastoral->imagens
            ->filter(fn (PastoralImagem $img) => in_array($img->id, $ids, true))
            ->take(4)
            ->values();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('pastorais.show', $pastoral) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
            <h1 class="text-xl font-semibold">Arte / prova social</h1>
        </div>
        <button type="button" wire:click="baixar" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Baixar imagem (Story 1080×1920)
        </button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[340px_1fr]">
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs text-zinc-500">Formato Instagram Story (9:16). Escolha até 4 fotos — cada uma entra recortada em quadrado, centralizada.</p>
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
                <p class="mb-2 text-sm font-medium">Fotos na arte (4)</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($todasFotos as $imagem)
                        <label class="block cursor-pointer">
                            <input type="checkbox" value="{{ $imagem->id }}" wire:model.live="fotosSelecionadas" class="mb-1">
                            <img src="{{ $imagem->url() }}" alt="" class="aspect-square w-full rounded object-cover">
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-auto rounded-xl border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mx-auto overflow-hidden rounded-sm shadow-none" style="width: 270px; height: 480px; background:#4a2e22; color:#f7f0e6; font-family: Georgia, serif;">
                <div class="flex h-full flex-col px-4 pt-5 pb-4 text-center">
                    <img src="{{ asset('images/logo.png') }}" alt="Fraternidade O Caminho" class="mx-auto mb-2 h-10 object-contain">
                    <p class="text-[8px] uppercase tracking-[0.22em]" style="color:#d4b896;">Fraternidade O Caminho · PJC</p>
                    <h2 class="mt-1 text-base font-semibold leading-tight">Pastoral de Rua</h2>
                    <p class="mt-0.5 text-[10px]" style="color:#e8d5b5;">{{ $mesNome }} · {{ $dataFormatada }}</p>

                    <div class="mt-3 grid grid-cols-2 gap-1">
                        @for($i = 0; $i < 4; $i++)
                            @php $foto = $fotos->get($i); @endphp
                            <div class="aspect-square overflow-hidden" style="background:#3a241a;">
                                @if($foto)
                                    <img src="{{ $foto->url() }}" alt="" class="h-full w-full object-cover">
                                @endif
                            </div>
                        @endfor
                    </div>

                    <div class="mt-3 flex flex-1 flex-col justify-between">
                        <div>
                            @if($frase)
                                <p class="text-[11px] leading-snug italic">“{{ $frase['texto'] }}”</p>
                                <p class="mt-1 text-[8px] uppercase tracking-widest" style="color:#d4b896;">{{ $frase['autor'] }}</p>
                            @endif
                            <p class="mt-2 text-[9px] leading-relaxed" style="color:#f3e6d4;">{{ $agradecimento }}</p>
                        </div>
                        <p class="text-[8px] uppercase tracking-[0.18em]" style="color:#d4b896;">Obrigado por caminhar conosco</p>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-zinc-500">Prévia em escala. O download sai em 1080×1920 px.</p>
        </div>
    </div>
</div>
