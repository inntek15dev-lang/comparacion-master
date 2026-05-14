<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class IaValidatorUserSeeder extends Seeder
{
    /**
     * Crea el usuario de sistema "IA_validator" usado como firmante
     * cuando la IA aprueba/rechaza un documento de acreditación.
     *
     * - Sin acceso de login (password inaccesible + sin roles de UI)
     * - Se referencia en datos_extraidos_ia.usuario_confirma_id
     *   y en documentos_cargados.asem_validador_id
     */
    public function run(): void
    {
        $existing = DB::table('users')->where('email', 'ia@sistema.internal')->first();

        if ($existing) {
            $this->command->info('Usuario IA_validator ya existe (ID: ' . $existing->id . '). Sin cambios.');
            return;
        }

        $userId = DB::table('users')->insertGetId([
            'name'              => 'IA_validator',
            'email'             => 'ia@sistema.internal',
            'user_type'         => 'asem',  // tipo requerido por la tabla users
            'password'          => Hash::make('__SISTEMA_NO_LOGIN__' . str()->random(32)),
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->command->info("✓ Usuario IA_validator creado con ID: {$userId}");
        $this->command->warn('  Guarda este ID en tu .env como: IA_VALIDATOR_USER_ID=' . $userId);
    }
}
