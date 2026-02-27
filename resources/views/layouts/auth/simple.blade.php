<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="auth-pjc min-h-svh antialiased">
    <div class="fixed inset-0">
        <div class="absolute inset-0 bg-stone-900 bg-repeat bg-center" style="background-image: url('{{ asset('images/pjc.png') }}')"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-stone-950/90 via-stone-900/50 to-stone-900/30"></div>
    </div>

    <div class="relative z-10 grid min-h-svh place-items-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-[420px]">
            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)]">
                <div class="border-b border-stone-200 bg-stone-50 px-8 py-6 text-center">
                    <a href="{{ route('home') }}" class="flex flex-col items-center gap-2" wire:navigate>
                        <img src="{{ asset('images/logo.png') }}" alt="Fraternidade O Caminho" class="h-24 object-contain" />
                        <span class="text-lg font-semibold tracking-tight text-stone-800">
                            Prestação de Contas<br>
                            <span class="text-stone-600">Pastoral de Rua</span>
                        </span>
                    </a>
                </div>
                <div class="px-8 py-8">
                    {{ $slot }}
                </div>
            </div>
            <p class="mt-6 text-center text-sm text-stone-400">
                Fraternidade O Caminho
            </p>
        </div>
    </div>
    @fluxScripts
</body>
</html>
