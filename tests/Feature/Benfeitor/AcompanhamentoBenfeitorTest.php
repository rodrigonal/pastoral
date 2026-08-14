<?php

use App\Models\Benfeitor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
    actingAs($this->user);
});

it('lista benfeitores com o membro da comunidade', function () {
    $membro = User::factory()->create(['name' => 'Frei João']);
    Benfeitor::factory()->create([
        'nome' => 'Ana Benfeitora',
        'membro_id' => $membro->id,
    ]);

    $this->get(route('benfeitores.index'))
        ->assertOk()
        ->assertSee('Ana Benfeitora')
        ->assertSee('Frei João');
});

it('cadastra benfeitor ligado a um membro', function () {
    Livewire::test('benfeitores.create')
        ->set('nome', 'Carlos Doador')
        ->set('membro_id', (string) $this->user->id)
        ->call('save')
        ->assertHasNoErrors();

    $benfeitor = Benfeitor::where('nome', 'Carlos Doador')->first();
    expect($benfeitor)->not->toBeNull();
    expect($benfeitor->membro_id)->toBe($this->user->id);
});

it('exige membro da comunidade no cadastro', function () {
    Livewire::test('benfeitores.create')
        ->set('nome', 'Sem Membro')
        ->set('membro_id', '')
        ->call('save')
        ->assertHasErrors(['membro_id']);
});
