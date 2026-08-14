<?php

use App\Support\ExtratoBancarioCatalogo;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use function Pest\Laravel\actingAs;
use App\Models\User;

it('cataloga o extrato da conta atual versionado no projeto', function () {
    $todos = ExtratoBancarioCatalogo::todos();

    expect($todos)->not->toBeEmpty();
    expect($todos[0]['existe'])->toBeTrue();
    expect(is_file($todos[0]['caminho']))->toBeTrue();
});

it('anexa o extrato anual de 2026 a prestacoes desse ano e nao a 2025', function () {
    $em2026 = ExtratoBancarioCatalogo::noPeriodo(
        Carbon::parse('2026-03-01'),
        Carbon::parse('2026-03-31'),
    );
    $em2025 = ExtratoBancarioCatalogo::noPeriodo(
        Carbon::parse('2025-03-01'),
        Carbon::parse('2025-03-31'),
    );

    expect($em2026)->not->toBeEmpty();
    expect($em2025)->toBeEmpty();
});

it('permite baixar o pdf do extrato versionado', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('tesouraria');

    $arquivo = ExtratoBancarioCatalogo::todos()[0]['arquivo'];

    $response = actingAs($user)->get(route('prestacao-contas.extrato', $arquivo));

    $response->assertOk();
    expect(str_starts_with($response->headers->get('Content-Type'), 'application/pdf'))->toBeTrue();
});
