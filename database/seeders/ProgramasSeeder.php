<?php

namespace Database\Seeders;

use App\Modules\Usuarios\Models\Programa;
use Illuminate\Database\Seeder;

class ProgramasSeeder extends Seeder
{
    public function run(): void
    {
        $programas = [
            'Administración de Empresas',
            'Contaduría Pública',
            'Derecho',
            'Economía',
            'Enfermería',
            'Ingeniería Agropecuaria',
            'Ingeniería Civil',
            'Ingeniería de Sistemas',
            'Medicina',
            'Nutrición y Dietética',
            'Odontología',
            'Psicología',
            'Trabajo Social',
        ];

        foreach ($programas as $nombre) {
            Programa::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
