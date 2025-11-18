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

        $adminEmail = 'admin@example.com';
        $adminPassword = 'admin12345';
        $admin = User::where('email', $adminEmail)->first();
        if (!$admin) {
            $admin = User::factory()->create([
                'name' => 'admin',
                'email' => $adminEmail,
                'password' => bcrypt($adminPassword),
                'security_color_answer' => 'azul',
                'security_animal_answer' => 'perro',
                'role' => 'admin',
            ]);
            echo "\nUsuario ADMIN creado (name=admin)\nEmail: $adminEmail\nContraseña: $adminPassword\n";
        } else {
            // Asegurar nombre y rol
            $admin->update(['name' => 'admin','role' => 'admin']);
            echo "\nUsuario ADMIN actualizado/confirmado (name=admin)\nEmail: $adminEmail\nContraseña: $adminPassword\n";
        }

        // Usuario operador demo
        $opEmail = 'operador@example.com';
        $opPassword = 'operador123';
        $operador = User::where('email', $opEmail)->first();
        if (!$operador) {
            $operador = User::factory()->create([
                'name' => 'operador',
                'email' => $opEmail,
                'password' => bcrypt($opPassword),
                'security_color_answer' => 'rojo',
                'security_animal_answer' => 'gato',
                'role' => 'operador',
            ]);
            echo "\nUsuario OPERADOR creado (name=operador)\nEmail: $opEmail\nContraseña: $opPassword\n";
        } else {
            $operador->update(['name' => 'operador','role' => 'operador']);
            echo "\nUsuario OPERADOR actualizado/confirmado (name=operador)\nEmail: $opEmail\nContraseña: $opPassword\n";
        }

        // Destinos base (para egresos)
        $this->call(\Database\Seeders\DestinoSeeder::class);
    }
}
