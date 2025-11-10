<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('nombre', 'admin')->first();

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrador Principal',
                'password' => Hash::make('admin123'),
                'perfil_id' => config('services.perfil_id'),
                'role_id' => $adminRole->id,
            ]
        );
    }
}
