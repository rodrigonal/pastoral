@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Pastoral de Rua" {{ $attributes }}>
        <x-slot name="logo" class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white p-1">
            <img src="{{ asset('images/logo.png') }}" alt="Fraternidade O Caminho" class="h-full w-full min-w-14 min-h-14 object-contain" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Prestação de Contas Pastoral de Rua" {{ $attributes }}>
        <x-slot name="logo" class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white p-1">
            <img src="{{ asset('images/logo.png') }}" alt="Fraternidade O Caminho" class="h-full w-full min-w-14 min-h-14 object-contain" />
        </x-slot>
    </flux:brand>
@endif
