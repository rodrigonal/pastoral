<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
#[Title('Usuários')]
class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'users' => User::with('roles')->orderBy('name')->paginate(15),
        ];
    }

    public function delete(int $id): void
    {
        $this->authorize('users.delete');

        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Você não pode excluir sua própria conta.');
            return;
        }

        $user->delete();
        session()->flash('message', 'Usuário excluído com sucesso.');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-semibold">Usuários</h1>
        @can('users.create')
        <a href="{{ route('users.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
            Novo Usuário
        </a>
        @endcan
    </div>

    @if (session('message'))
        <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ session('message') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-400">{{ session('error') }}</div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Usuário</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">E-mail</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Perfil</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-4 py-2">{{ $user->name }}</td>
                            <td class="px-4 py-2">{{ $user->username }}</td>
                            <td class="px-4 py-2">{{ $user->email }}</td>
                            <td class="px-4 py-2">
                                @foreach($user->roles as $role)
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-200">{{ ucfirst($role->name) }}</span>
                                @endforeach
                                @if($user->roles->isEmpty())
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @can('users.update')
                                <a href="{{ route('users.edit', $user) }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">Editar</a>
                                @else
                                -
                                @endcan
                                @can('users.delete')
                                    @if($user->id !== auth()->id())
                                        <span class="text-zinc-400">|</span>
                                        <button wire:click="delete({{ $user->id }})" wire:confirm="Excluir este usuário?" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">Excluir</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">Nenhum usuário encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 px-4 pb-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
