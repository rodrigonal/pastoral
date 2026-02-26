<?php

use App\Models\Lancamento;
use function Pest\Laravel\actingAs;
use App\Models\Segmento;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('admin acessa lancamentos create', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin)->get(route('lancamentos.create'))->assertOk();
});

it('tesouraria acessa lancamentos create', function () {
    $tesouraria = User::factory()->create();
    $tesouraria->assignRole('tesouraria');

    actingAs($tesouraria)->get(route('lancamentos.create'))->assertOk();
});

it('visualizador nao acessa lancamentos create', function () {
    $visualizador = User::factory()->create();
    $visualizador->assignRole('visualizador');

    actingAs($visualizador)->get(route('lancamentos.create'))->assertForbidden();
});

it('visualizador nao acessa lancamentos edit', function () {
    $visualizador = User::factory()->create();
    $visualizador->assignRole('visualizador');
    $segmento = Segmento::factory()->create();
    $lancamento = Lancamento::factory()->create(['user_id' => $visualizador->id]);
    $lancamento->segmentos()->attach($segmento);

    actingAs($visualizador)->get(route('lancamentos.edit', $lancamento))->assertForbidden();
});
