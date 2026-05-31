<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = ['propietario', 'vendedor', 'cliente'];

        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }

        $admin = User::updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'Propietario',
            'password' => Hash::make('123456789'),
        ]);

        $admin->syncRoles(['propietario']);
    }
}
