<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mandante; // Necesario si creamos usuarios de mandante
use App\Models\Contratista; // Necesario si creamos usuarios de contratista
use Illuminate\Support\Facades\Hash; // Para hashear contraseñas
use Spatie\Permission\Models\Role; // Para asignar roles

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // 1. Usuario ASEM Admin
        $asemAdminUser = User::firstOrCreate(
            ['email' => 'admin.asem@example.com'], // Usar un email distintivo para buscar/crear
            [
                'name' => 'Administrador ASEM Global',
                'password' => Hash::make('password123'), // ¡Cambiar esto!
                'user_type' => 'asem',
                'is_platform_admin' => false, // No aplica a ASEM
                'is_active' => true,
                // mandante_id y contratista_id son NULL por defecto (o explícitamente)
            ]
        );
        if ($asemAdminUser->wasRecentlyCreated || !$asemAdminUser->hasRole('ASEM_Admin')) {
             $asemAdminUser->assignRole('ASEM_Admin'); // Asignar rol
        }


        // 2. Usuario ASEM Validador
        $asemValidatorUser = User::firstOrCreate(
            ['email' => 'validator.asem@example.com'],
            [
                'name' => 'Validador ASEM',
                'password' => Hash::make('password123'),
                'user_type' => 'asem',
                'is_active' => true,
            ]
        );
        if ($asemValidatorUser->wasRecentlyCreated || !$asemValidatorUser->hasRole('ASEM_Validator')) {
            $asemValidatorUser->assignRole('ASEM_Validator');
        }


        // 3. Crear un Mandante de Ejemplo (si no se crea en otro Seeder)
        $mandanteEjemplo = Mandante::firstOrCreate(
            ['rut' => '77.777.777-7'],
            [
                'razon_social' => 'Empresa Mandante Ejemplo SA',
                'persona_contacto_nombre' => 'Contacto Mandante',
                'persona_contacto_email' => 'contacto@mandanteejemplo.com',
                'persona_contacto_telefono' => '123456789',
                'is_active' => true,
            ]
        );

        // Usuario Administrador para el Mandante de Ejemplo
        if ($mandanteEjemplo) {
            $mandanteAdminUser = User::firstOrCreate(
                ['email' => 'admin@mandanteejemplo.com'],
                [
                    'name' => 'Admin ' . $mandanteEjemplo->razon_social,
                    'password' => Hash::make('password123'),
                    'user_type' => 'mandante',
                    'mandante_id' => $mandanteEjemplo->id,
                    'is_platform_admin' => true, // Este es el admin de su empresa
                    'is_active' => true,
                ]
            );
            if ($mandanteAdminUser->wasRecentlyCreated || !$mandanteAdminUser->hasRole('Mandante_Admin')) {
                $mandanteAdminUser->assignRole('Mandante_Admin');
            }
        }


        // 4. Crear un Contratista de Ejemplo (requiere rubro_id y tipo_empresa_legal_id)
        $rubroEjemplo = \App\Models\Rubro::firstOrCreate(['nombre' => 'Construcción General Test'], ['is_active' => true]);
        $tipoEmpresaEjemplo = \App\Models\TipoEmpresaLegal::firstOrCreate(['nombre' => 'Sociedad por Acciones Test'], ['is_active' => true]);


        $contratistaEjemplo = Contratista::firstOrCreate(
            ['rut' => '88.888.888-8'],
            [
                'razon_social' => 'Constructora Contratista Ejemplo SpA',
                'nombre_fantasia' => 'Contratista Alfa',
                'admin_plataforma_nombres' => 'Admin',
                'admin_plataforma_apellido_paterno' => 'Contratista',
                'admin_plataforma_apellido_materno' => 'Usuario',
                'admin_plataforma_cargo' => 'Gerente de Operaciones',
                'admin_plataforma_email' => 'admin.plataforma@contratistaejemplo.com',
                'admin_plataforma_telefono' => '987654321',
                'direccion_pais' => 'Chile',
                'direccion_calle' => 'Calle Falsa',
                'direccion_numero' => '123',
                'direccion_region' => 'Metropolitana',
                'direccion_comuna' => 'Santiago',
                'telefono_empresa' => '11223344',
                'email_empresa' => 'contacto@contratistaejemplo.com',
                'tipo_empresa_legal_id' => $tipoEmpresaEjemplo->id,
                'rubro_id' => $rubroEjemplo->id,
                'rep_legal_nombres' => 'Representante',
                'rep_legal_apellido_paterno' => 'Legal',
                'rep_legal_apellido_materno' => 'Ejemplo',
                'rep_legal_rut' => '11.111.111-1',
                'rep_legal_telefono' => '22334455',
                'rep_legal_email' => 'legal@contratistaejemplo.com',
                'tipo_clasificacion' => 'Contratista Principal',
                'cantidad_trabajadores_rango' => '11-50',
                'entidades_a_controlar' => json_encode(['empresa', 'trabajadores']),
                'is_active' => true,
            ]
        );

        // Usuario Administrador para el Contratista de Ejemplo
        if ($contratistaEjemplo) {
            $contratistaAdminUser = User::firstOrCreate(
                ['email' => 'admin@contratistaejemplo.com'],
                [
                    'name' => 'Admin ' . $contratistaEjemplo->razon_social,
                    'password' => Hash::make('password123'),
                    'user_type' => 'contratista',
                    'contratista_id' => $contratistaEjemplo->id,
                    'is_platform_admin' => true,
                    'is_active' => true,
                ]
            );
            if ($contratistaAdminUser->wasRecentlyCreated || !$contratistaAdminUser->hasRole('Contratista_Admin')) {
                 $contratistaAdminUser->assignRole('Contratista_Admin');
            }
        }

        $this->command->info('Usuarios de ejemplo creados y roles asignados.');
    }
}