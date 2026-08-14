<?php

use App\Models\ControleSaldo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('exibe página de controle de saldos autenticado', function () {
    $user = User::factory()->create();
    $user->assignRole('visualizador');
    actingAs($user);

    $this->get(route('controle-saldos'))->assertOk();
});

it('tesouraria pode atualizar saldo em mãos', function () {
    $user = User::factory()->create();
    $user->assignRole('tesouraria');
    actingAs($user);

    Livewire::test('controle-saldos')
        ->set('saldo_em_maos', '150,50')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) ControleSaldo::registro()->saldo_em_maos)->toBe(150.5);
});

it('visualizador não pode salvar saldo em mãos', function () {
    $user = User::factory()->create();
    $user->assignRole('visualizador');
    actingAs($user);

    Livewire::test('controle-saldos')
        ->set('saldo_em_maos', '999')
        ->call('save')
        ->assertForbidden();
});
