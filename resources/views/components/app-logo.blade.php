@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Pastoral de Rua" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('images/pjc.png') }}" alt="PJC" class="size-full object-cover" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Prestação de Contas Pastoral de Rua" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('images/pjc.png') }}" alt="PJC" class="size-full object-cover" />
        </x-slot>
    </flux:brand>
@endif
