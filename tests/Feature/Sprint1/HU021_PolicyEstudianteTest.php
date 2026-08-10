<?php

use App\Models\User;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Support\Facades\Gate;

describe('HU-021 — Policy: aislamiento de datos entre estudiantes', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
    });

    it('un estudiante puede ver su propio diligenciamiento', function () {
        $estudiante = User::factory()->create();
        $estudiante->assignRole('estudiante');

        $instrumento = Instrumento::first();
        $diligenciamiento = Diligenciamiento::create([
            'user_id'        => $estudiante->id,
            'instrumento_id' => $instrumento->id,
            'estado'         => 'en_progreso',
        ]);

        $puede = Gate::forUser($estudiante)->allows('view', $diligenciamiento);
        expect($puede)->toBeTrue();
    });

    it('un estudiante NO puede ver el diligenciamiento de otro estudiante — devuelve 403', function () {
        [$estudianteA, $estudianteB] = User::factory()->count(2)->create();
        $estudianteA->assignRole('estudiante');
        $estudianteB->assignRole('estudiante');

        $instrumento = Instrumento::first();

        // Diligenciamiento pertenece a estudiante A
        $diligenciamiento = Diligenciamiento::create([
            'user_id'        => $estudianteA->id,
            'instrumento_id' => $instrumento->id,
            'estado'         => 'en_progreso',
        ]);

        // Estudiante B intenta ver el diligenciamiento de A
        $puede = Gate::forUser($estudianteB)->allows('view', $diligenciamiento);
        expect($puede)->toBeFalse();
    });

    it('un profesional de salud puede ver el diligenciamiento de cualquier estudiante', function () {
        $estudiante  = User::factory()->create();
        $profesional = User::factory()->create();
        $estudiante->assignRole('estudiante');
        $profesional->assignRole('profesional_salud');

        $instrumento = Instrumento::first();
        $diligenciamiento = Diligenciamiento::create([
            'user_id'        => $estudiante->id,
            'instrumento_id' => $instrumento->id,
            'estado'         => 'completado',
        ]);

        $puede = Gate::forUser($profesional)->allows('view', $diligenciamiento);
        expect($puede)->toBeTrue();
    });

    it('un estudiante no puede actualizar el diligenciamiento de otro', function () {
        [$estudianteA, $estudianteB] = User::factory()->count(2)->create();
        $estudianteA->assignRole('estudiante');
        $estudianteB->assignRole('estudiante');

        $instrumento = Instrumento::first();
        $diligenciamiento = Diligenciamiento::create([
            'user_id'        => $estudianteA->id,
            'instrumento_id' => $instrumento->id,
            'estado'         => 'en_progreso',
        ]);

        $puede = Gate::forUser($estudianteB)->allows('update', $diligenciamiento);
        expect($puede)->toBeFalse();
    });

});
