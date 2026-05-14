<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; // Para asignar un rol a un usuario de ejemplo

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Permisos para Mandantes ---
        Permission::firstOrCreate(['name' => 'define document requirements', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view contractor compliance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage own company users', 'guard_name' => 'web']); // Mandante gestiona sus propios usuarios
        // <<< NUEVO >>> Permiso específico para la validación de segundo nivel.
        Permission::firstOrCreate(['name' => 'validate mandante documents', 'guard_name' => 'web']); 

        // --- Permisos para Contratistas ---
        Permission::firstOrCreate(['name' => 'upload documents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view own document status', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage own company employees', 'guard_name' => 'web']); // Contratista gestiona sus trabajadores
        Permission::firstOrCreate(['name' => 'manage own contractor users', 'guard_name' => 'web']); // Contratista gestiona sus propios usuarios de plataforma


        // --- Permisos para ASEM (Validación y Administración) ---
        Permission::firstOrCreate(['name' => 'validate documents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage mandantes', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage contratistas', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage all users', 'guard_name' => 'web']); // ASEM gestiona todos los usuarios
        Permission::firstOrCreate(['name' => 'access asem dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage universal lists', 'guard_name' => 'web']); // Para gestionar rubros, tipos_condicion, etc.
        Permission::firstOrCreate(['name' => 'manage platform settings', 'guard_name' => 'web']);


        // --- Crear Roles ---
        $roleAsemAdmin = Role::firstOrCreate(['name' => 'ASEM_Admin', 'guard_name' => 'web']);
        $roleAsemValidator = Role::firstOrCreate(['name' => 'ASEM_Validator', 'guard_name' => 'web']);
        $roleMandanteAdmin = Role::firstOrCreate(['name' => 'Mandante_Admin', 'guard_name' => 'web']);
        // <<< NUEVO >>> Rol para usuarios del mandante con capacidad de validación.
        $roleMandanteValidator = Role::firstOrCreate(['name' => 'Mandante_Validator', 'guard_name' => 'web']);
        // <<< NUEVO >>> Rol para usuarios del mandante con acceso de solo lectura (sin capacidad de modificar datos).
        $roleMandanteVer = Role::firstOrCreate(['name' => 'Mandante_Ver', 'guard_name' => 'web']);
        $roleContratistaAdmin = Role::firstOrCreate(['name' => 'Contratista_Admin', 'guard_name' => 'web']);
        // <<< NUEVO >>> Rol para usuarios secundarios de contratistas (gestión de recursos por vinculación)
        $roleContratistaUser = Role::firstOrCreate(['name' => 'Contratista_User', 'guard_name' => 'web']);


        // --- Asignar Permisos a Roles ---
        // ASEM Admin tiene todos los permisos
        $roleAsemAdmin->givePermissionTo(Permission::all()); 

        $roleAsemValidator->givePermissionTo([
            'validate documents',
            'access asem dashboard',
            'view contractor compliance',
        ]);

        $roleMandanteAdmin->givePermissionTo([
            'define document requirements',
            'view contractor compliance',
            'manage own company users',
            'validate mandante documents', // <<< MODIFICADO >>> El Admin del Mandante también puede validar.
        ]);
        
        // <<< NUEVO >>> El Validador del Mandante solo puede ver el cumplimiento y validar.
        $roleMandanteValidator->givePermissionTo([
            'view contractor compliance',
            'validate mandante documents',
        ]);

        $roleContratistaAdmin->givePermissionTo([
            'upload documents',
            'view own document status',
            'manage own company employees',
            'manage own contractor users',
        ]);

        $roleMandanteVer->givePermissionTo([
            'view contractor compliance',
        ]);

        // <<< NUEVO >>> Contratista_User tiene permisos básicos de carga y visualización
        $roleContratistaUser->givePermissionTo([
            'upload documents',
            'view own document status',
        ]);

        // <<< NUEVO >>> Rol para Sub-Contratistas (usuarios de empresas sub-contratistas)
        // NO puede crear usuarios, solo gestionar sus recursos
        $roleSubcontratista = Role::firstOrCreate(['name' => 'Subcontratista', 'guard_name' => 'web']);
        $roleSubcontratista->givePermissionTo([
            'upload documents',
            'view own document status',
            'manage own company employees', // Puede gestionar trabajadores de su empresa sub-contratista
        ]);

        // <<< NUEVO >>> Roles para el módulo de Verificación Laboral
        // Solo pueden ser asignados por OVAL_ADMIN (ASEM_Admin)
        // Los permisos se asignarán conforme se desarrollen los módulos
        $roleVerificaSupervisor = Role::firstOrCreate(['name' => 'Verifica_Supervisor', 'guard_name' => 'web']);
        $roleVerificaAnalista = Role::firstOrCreate(['name' => 'Verifica_Analista', 'guard_name' => 'web']);
        $roleVerificaAuditor = Role::firstOrCreate(['name' => 'Verifica_Auditor', 'guard_name' => 'web']);
        $roleVerificaEmisor = Role::firstOrCreate(['name' => 'Verifica_Emisor', 'guard_name' => 'web']);

        $this->command->info('Roles y Permisos creados y asignados exitosamente.');
    }
}