<?php

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app')]
#[Title('Editar Usuário')]
class extends Component {
    public User $user;

    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? 'visualizador';
    }

    public function save(): void
    {
        $this->authorize('users.update');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $this->user->id],
            'email' => ['required', 'email', 'unique:users,email,' . $this->user->id],
            'role' => ['required', 'exists:roles,name'],
        ];

        if ($this->password) {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        $this->validate($rules);

        $this->user->update([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
        ]);

        if ($this->password) {
            $this->user->update(['password' => bcrypt($this->password)]);
        }

        $this->user->syncRoles([$this->role]);

        session()->flash('message', 'Usuário atualizado com sucesso.');
        $this->redirect(route('users.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('users.index') }}" wire:navigate class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">← Voltar</a>
        <h1 class="text-xl font-semibold">Editar Usuário</h1>
    </div>

    <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Nome *</label>
                <input type="text" wire:model="name" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('name') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Usuário (login) *</label>
                <input type="text" wire:model="username" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('username') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">E-mail *</label>
                <input type="email" wire:model="email" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                @error('email') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nova senha (deixe em branco para manter)</label>
                <input type="password" wire:model="password" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
                @error('password') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </div>
            @if($password)
            <div>
                <label class="mb-1 block text-sm font-medium">Confirmar nova senha</label>
                <input type="password" wire:model="password_confirmation" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700">
            </div>
            @endif
            <div>
                <label class="mb-1 block text-sm font-medium">Perfil *</label>
                <select wire:model="role" class="w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700" required>
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                    Salvar
                </button>
                <a href="{{ route('users.index') }}" wire:navigate class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">Cancelar</a>
            </div>
        </form>
    </div>
</div>
