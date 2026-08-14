<?php

use App\Models\ControleSaldo;
use App\Services\SaldoService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')]
#[Title('Controle de Saldos')]
class extends Component {
    public string $saldo_em_maos = '';

    public function mount(): void
    {
        $v = (float) ControleSaldo::registro()->saldo_em_maos;
        $this->saldo_em_maos = number_format($v, 2, ',', '.');
    }

    public function save(): void
    {
        $this->authorize('lancamentos.update');

        $valor = (float) str_replace(',', '.', str_replace('.', '', preg_replace('/R\$\s*/', '', $this->saldo_em_maos)));

        if ($valor < 0) {
            $this->addError('saldo_em_maos', 'O valor não pode ser negativo.');

            return;
        }

        ControleSaldo::registro()->update(['saldo_em_maos' => $valor]);

        session()->flash('message', 'Saldo em mãos atualizado com sucesso.');
    }

    public function with(): array
    {
        $saldoService = app(SaldoService::class);

        return [
            'saldoAcumulado' => $saldoService->saldoAcumulado(),
            'saldoEmMaosAtual' => $saldoService->saldoEmMaos(),
            'saldoEmConta' => $saldoService->saldoEmConta(),
            'saldoLegado' => $saldoService->saldoLegado(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Controle de saldos</h1>
        <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Dashboard</a>
    </div>

    @if (session('message'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/50 dark:bg-green-950/40 dark:text-green-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo acumulado</h3>
            <p class="mt-2 text-2xl font-semibold {{ $saldoAcumulado >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($saldoAcumulado, 2, ',', '.') }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Livro caixa da conta atual: todos os lançamentos − saldo antigo da conta do CS (exceto reembolsos).</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo em mãos</h3>
            <p class="mt-2 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                R$ {{ number_format($saldoEmMaosAtual, 2, ',', '.') }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Dinheiro em espécie ainda não depositado (valor informado manualmente).</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo em conta</h3>
            <p class="mt-2 text-2xl font-semibold {{ $saldoEmConta >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                R$ {{ number_format($saldoEmConta, 2, ',', '.') }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Conta Bradesco em uso. Calculado: saldo acumulado − saldo em mãos (já sem o saldo antigo da conta do CS).</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Saldo antigo (ficou na conta do CS)</h3>
            <p class="mt-2 text-2xl font-semibold text-zinc-600 dark:text-zinc-300">
                R$ {{ number_format($saldoLegado, 2, ',', '.') }}
            </p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Valor que ficou na conta antiga, inacessível. Não entra no saldo da conta atual.</p>
        </div>
    </div>

    @can('lancamentos.update')
        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="mb-4 text-lg font-semibold">Atualizar saldo em mãos</h2>
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium">Valor em espécie (R$)</label>
                    <input type="text" wire:model="saldo_em_maos" inputmode="decimal" placeholder="0,00"
                        class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                    @error('saldo_em_maos')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit"
                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Salvar
                </button>
            </form>
        </div>
    @else
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Apenas usuários com permissão de edição de lançamentos podem alterar o saldo em mãos.</p>
    @endcan
</div>
