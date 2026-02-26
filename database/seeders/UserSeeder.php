<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador',
                'email' => 'admin@pjc.local',
                'password' => bcrypt('pastoral'),
            ]
        );
        $admin->assignRole('admin');

        $coord = User::firstOrCreate(
            ['username' => 'coord'],
            [
                'name' => 'Coordenador',
                'email' => 'coord@pjc.local',
                'password' => bcrypt('coord'),
            ]
        );
        $coord->assignRole('coord');
    }
}
