<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'benfeitores.view',
            'benfeitores.create',
            'benfeitores.update',
            'benfeitores.delete',
            'pastorais.view',
            'pastorais.create',
            'pastorais.update',
            'pastorais.delete',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $admin = Role::query()->where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }

        $tesouraria = Role::query()->where('name', 'tesouraria')->first();
        if ($tesouraria) {
            $tesouraria->givePermissionTo([
                'benfeitores.view', 'benfeitores.create', 'benfeitores.update', 'benfeitores.delete',
                'pastorais.view', 'pastorais.create', 'pastorais.update', 'pastorais.delete',
            ]);
        }

        $visualizador = Role::query()->where('name', 'visualizador')->first();
        if ($visualizador) {
            $visualizador->givePermissionTo(['benfeitores.view', 'pastorais.view']);
        }

        $coord = Role::query()->where('name', 'coord')->first();
        if ($coord) {
            $coord->givePermissionTo([
                'benfeitores.view',
                'pastorais.view', 'pastorais.create', 'pastorais.update', 'pastorais.delete',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        //
    }
};
