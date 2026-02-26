<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Prestação de Contas')]
class extends Component {
    public int $mes;
    public int $ano;

    public function mount(): void
    {
        $this->mes = (int) now()->format('m');
        $this->ano = (int) now()->format('Y');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <h1 class="text-xl font-semibold">Prestação de Contas Mensal</h1>

    <div class="max-w-md rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        @can('prestacao-contas.export')
        <form action="{{ route('prestacao-contas.pdf') }}" method="POST" target="_blank" class="space-y-4">
            @csrf
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
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                Gerar PDF
            </button>
        </form>
        @else
        <p class="text-zinc-500">Você não tem permissão para gerar PDF.</p>
        @endcan
    </div>
</div>
