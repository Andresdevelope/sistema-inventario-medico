<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $adminEmail = env('SEED_ADMIN_EMAIL', 'admin@example.com');
        $adminPassword = env('SEED_ADMIN_PASSWORD', Str::random(24));
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
            echo "\nUsuario ADMIN creado (name=admin)\nEmail: $adminEmail\n";
        } else {
            // Asegurar nombre y rol
            $admin->update(['name' => 'admin','role' => 'admin']);
            echo "\nUsuario ADMIN actualizado/confirmado (name=admin)\nEmail: $adminEmail\n";
        }

        // Usuario operador demo
        $opEmail = env('SEED_OPERADOR_EMAIL', 'operador@example.com');
        $opPassword = env('SEED_OPERADOR_PASSWORD', Str::random(24));
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
            echo "\nUsuario OPERADOR creado (name=operador)\nEmail: $opEmail\n";
        } else {
            $operador->update(['name' => 'operador','role' => 'operador']);
            echo "\nUsuario OPERADOR actualizado/confirmado (name=operador)\nEmail: $opEmail\n";
        }

        // Destinos base (para egresos)
        $this->call(\Database\Seeders\DestinoSeeder::class);
    }
}
