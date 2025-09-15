<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $email = 'test@example.com';
        $password = 'inventario123';
        if (!\App\Models\User::where('email', $email)->exists()) {
            \App\Models\User::factory()->create([
                'name' => 'Test User',
                'email' => $email,
                'password' => bcrypt($password),
                'security_color_answer' => 'azul',
                'security_animal_answer' => 'perro',
            ]);
            echo "\nUsuario de prueba creado:\nEmail: $email\nContraseña: $password\n";
        } else {
            echo "\nUsuario de prueba ya existe:\nEmail: $email\nContraseña: $password\n";
        }
    }
}
