<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'lancamentos.view',
            'lancamentos.create',
            'lancamentos.update',
            'lancamentos.delete',
            'prestacao-contas.view',
            'prestacao-contas.export',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        $tesouraria = Role::firstOrCreate(['name' => 'tesouraria']);
        $tesouraria->givePermissionTo([
            'lancamentos.view', 'lancamentos.create', 'lancamentos.update', 'lancamentos.delete',
            'prestacao-contas.view', 'prestacao-contas.export',
            'users.view',
        ]);

        $visualizador = Role::firstOrCreate(['name' => 'visualizador']);
        $visualizador->givePermissionTo(['lancamentos.view', 'prestacao-contas.view', 'users.view']);

        $coord = Role::firstOrCreate(['name' => 'coord']);
        $coord->givePermissionTo(['lancamentos.view', 'prestacao-contas.view', 'users.view']);
    }
}
