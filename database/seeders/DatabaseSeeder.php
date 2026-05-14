<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,    // Roles y permisos primero
            UserSeeder::class,                   // Luego usuarios y empresas de ejemplo
            NombreDocumentoSeeder::class,        // AHORA incluimos el seeder de NombreDocumento
            // Aquí podríamos añadir más seeders específicos para catálogos si se vuelven muy grandes:
            // NacionalidadSeeder::class,
            // RubroSeeder::class,
            // TipoCondicionSeeder::class,
            // ...etc. si los crearas como Seeders individuales.
        ]);

        $this->command->info('¡Base de datos sembrada con datos iniciales y tipos de documento!');
    }
}