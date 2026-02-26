<?php

use App\Models\Segmento;
use function Pest\Laravel\actingAs;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('tesouraria');
});

it('gera pdf da prestacao de contas', function () {
    $response = actingAs($this->user)
        ->post(route('prestacao-contas.pdf'), [
            'mes' => now()->month,
            'ano' => now()->year,
            '_token' => csrf_token(),
        ]);

    $response->assertOk();
    expect(str_starts_with($response->headers->get('Content-Type'), 'application/pdf'))->toBeTrue();
});
