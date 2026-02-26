<x-layouts::auth :title="__('Entrar')">
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <flux:input
                name="username"
                :label="__('Usuário')"
                :value="old('username')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="admin"
            />

            <div class="space-y-2">
                <flux:input
                    name="password"
                    :label="__('Senha')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Senha')"
                    viewable
                />
                @if (Route::has('password.request'))
                    <div class="flex justify-end">
                        <flux:link href="{{ route('password.request') }}" wire:navigate class="text-sm text-stone-500 hover:text-stone-700">
                            {{ __('Esqueceu a senha?') }}
                        </flux:link>
                    </div>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Lembrar de mim')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Entrar') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
