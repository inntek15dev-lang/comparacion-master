<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NombreDocumento;

class NombreDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NombreDocumento::firstOrCreate(
            ['nombre' => 'Cédula de Identidad (Anverso y Reverso)'],
            [
                'descripcion' => 'Documento nacional de identidad vigente.',
                'aplica_a' => 'trabajador',
                'is_active' => true
            ]
        );

        NombreDocumento::firstOrCreate(
            ['nombre' => 'Contrato de Trabajo (Firmado)'],
            [
                'descripcion' => 'Copia del contrato laboral vigente y firmado por ambas partes.',
                'aplica_a' => 'trabajador',
                'is_active' => true
            ]
        );

        NombreDocumento::firstOrCreate(
            ['nombre' => 'Certificado de Antecedentes Laborales y Previsionales'],
            [
                'descripcion' => 'También conocido como F30 o F30-1, emitido por la Dirección del Trabajo.',
                'aplica_a' => 'empresa',
                'is_active' => true
            ]
        );

        NombreDocumento::firstOrCreate(
            ['nombre' => 'Póliza de Seguro de Responsabilidad Civil'],
            [
                'descripcion' => 'Póliza vigente que cubra los riesgos de la actividad.',
                'aplica_a' => 'empresa',
                'is_active' => true
            ]
        );

        NombreDocumento::firstOrCreate(
            ['nombre' => 'Examen Médico Ocupacional'],
            [
                'descripcion' => 'Certificado de aptitud médica para el cargo específico.',
                'aplica_a' => 'trabajador',
                'is_active' => true
            ]
        );

        $this->command->info('Tipos de documento genéricos creados.');
    }
}