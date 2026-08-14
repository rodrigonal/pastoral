<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware(['role:admin|tesouraria|visualizador|coord'])->group(function () {
        Volt::route('dashboard', 'dashboard')->name('dashboard');
        Volt::route('controle-saldos', 'controle-saldos')->name('controle-saldos');
    });

    Route::middleware(['permission:lancamentos.view'])->group(function () {
        Volt::route('lancamentos', 'lancamentos.index')->name('lancamentos.index');
        Route::get('lancamentos/{lancamento}/anexo', [\App\Http\Controllers\LancamentoAnexoController::class, 'download'])->name('lancamentos.anexo');
    });

    Route::middleware(['permission:benfeitores.create'])->group(function () {
        Volt::route('benfeitores/create', 'benfeitores.create')->name('benfeitores.create');
    });

    Route::middleware(['permission:benfeitores.view'])->group(function () {
        Volt::route('benfeitores', 'benfeitores.index')->name('benfeitores.index');
        Volt::route('benfeitores/{benfeitor}', 'benfeitores.show')->name('benfeitores.show');
    });

    Route::middleware(['permission:benfeitores.update'])->group(function () {
        Volt::route('benfeitores/{benfeitor}/edit', 'benfeitores.edit')->name('benfeitores.edit');
    });

    Route::middleware(['permission:pastorais.create'])->group(function () {
        Volt::route('pastorais/create', 'pastorais.create')->name('pastorais.create');
    });

    Route::middleware(['permission:pastorais.view'])->group(function () {
        Volt::route('pastorais', 'pastorais.index')->name('pastorais.index');
        Volt::route('pastorais/{pastoral}/arte', 'pastorais.arte')->name('pastorais.arte');
        Volt::route('pastorais/{pastoral}', 'pastorais.show')->name('pastorais.show');
    });

    Route::middleware(['permission:lancamentos.create'])->group(function () {
        Volt::route('lancamentos/create', 'lancamentos.create')->name('lancamentos.create');
    });

    Route::middleware(['permission:lancamentos.update'])->group(function () {
        Volt::route('lancamentos/{lancamento}/edit', 'lancamentos.edit')->name('lancamentos.edit');
    });

    Route::middleware(['permission:prestacao-contas.view'])->group(function () {
        Volt::route('prestacao-contas', 'prestacao-contas.relatorio-mensal')->name('prestacao-contas.index');
    });

    Route::middleware(['permission:prestacao-contas.export'])->group(function () {
        Route::post('prestacao-contas/pdf', [\App\Http\Controllers\PrestacaoContasController::class, 'download'])->name('prestacao-contas.pdf');
    });

    Route::middleware(['permission:users.view'])->group(function () {
        Volt::route('users', 'users.index')->name('users.index');
    });

    Route::middleware(['permission:users.create'])->group(function () {
        Volt::route('users/create', 'users.create')->name('users.create');
    });

    Route::middleware(['permission:users.update'])->group(function () {
        Volt::route('users/{user}/edit', 'users.edit')->name('users.edit');
    });
});

require __DIR__.'/settings.php';
