<?php

use App\Models\Pastoral;
use App\Models\PastoralImagem;
use App\Models\User;
use App\Support\FrasesPastorais;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
    actingAs($this->user);
    Storage::fake('public');
});

it('tem um arquivo com diversas frases pastorais', function () {
    $frases = FrasesPastorais::todas();

    expect($frases)->toHaveCount(32);
    expect(FrasesPastorais::find(1)['texto'])->toContain('mãos se unem');
    expect(FrasesPastorais::aleatoria())->toHaveKeys(['id', 'texto', 'autor']);
});

it('cria pastoral e envia fotos', function () {
    Livewire::test('pastorais.create')
        ->set('data', now()->format('Y-m-d'))
        ->set('titulo', 'Pastoral de agosto')
        ->call('save')
        ->assertHasNoErrors();

    $pastoral = Pastoral::first();
    expect($pastoral)->not->toBeNull();
    expect($pastoral->titulo)->toBe('Pastoral de agosto');

    $foto = UploadedFile::fake()->image('acao.jpg', 800, 600);

    Livewire::test('pastorais.show', ['pastoral' => $pastoral])
        ->set('fotos', [$foto])
        ->call('uploadFotos')
        ->assertHasNoErrors();

    expect(PastoralImagem::count())->toBe(1);
    Storage::disk('public')->assertExists(PastoralImagem::first()->path);
});

it('abre a tela da arte com frase e data', function () {
    $pastoral = Pastoral::factory()->create([
        'user_id' => $this->user->id,
        'frase_id' => 1,
        'data' => '2026-08-10',
    ]);

    $this->get(route('pastorais.arte', $pastoral))
        ->assertOk()
        ->assertSee('Pastoral de Rua')
        ->assertSee('10/08/2026');
});
