<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Prestação de Contas')]
class extends Component {
    public int $mes;
    public int $ano;
    public bool $usarPeriodo = false;
    public int $mesInicio;
    public int $anoInicio;
    public int $mesFim;
    public int $anoFim;

    public function mount(): void
    {
        $this->mes = (int) now()->format('m');
        $this->ano = (int) now()->format('Y');
        $this->mesInicio = $this->mes;
        $this->anoInicio = $this->ano;
        $this->mesFim = $this->mes;
        $this->anoFim = $this->ano;
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <h1 class="text-xl font-semibold">Prestação de Contas</h1>

    <div class="max-w-md rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        @can('prestacao-contas.export')
        <form action="{{ route('prestacao-contas.pdf') }}" method="POST" target="_blank" class="space-y-4">
            @csrf
            <div class="flex items-center gap-2">
                <input type="checkbox" name="usar_periodo" id="usar_periodo" value="1" wire:model.live="usarPeriodo"
                    class="rounded border-zinc-300 dark:border-zinc-600">
                <label for="usar_periodo" class="text-sm font-medium">Gerar PDF de vários meses</label>
            </div>

            @if($usarPeriodo)
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Mês inicial</label>
                    <select name="mes_inicio" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $mesInicio === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Ano inicial</label>
                    <input type="number" name="ano_inicio" value="{{ $anoInicio }}" min="2020" max="2030" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Mês final</label>
                    <select name="mes_fim" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $mesFim === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Ano final</label>
                    <input type="number" name="ano_fim" value="{{ $anoFim }}" min="2020" max="2030" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                </div>
            </div>
            @else
            <div>
                <label class="mb-1 block text-sm font-medium">Mês</label>
                <select name="mes" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $mes === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Ano</label>
                <input type="number" name="ano" value="{{ $ano }}" min="2020" max="2030" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
            </div>
            @endif

            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                Gerar PDF
            </button>
        </form>
        @else
        <p class="text-zinc-500">Você não tem permissão para gerar PDF.</p>
        @endcan
    </div>
</div>
