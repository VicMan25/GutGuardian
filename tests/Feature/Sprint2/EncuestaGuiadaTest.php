<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Respuesta;
use App\Modules\Encuestas\Models\Seccion;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

/**
 * Encuesta guiada (una pregunta por paso). Es solo presentación: estos tests
 * fijan que el formulario de la sección sigue conteniendo TODAS las preguntas
 * con sus inputs (los pasos ocultos también se envían) y que el paso inicial
 * respeta el reanudar y los errores de validación.
 */
describe('Encuesta guiada — presentación por pasos sin cambiar el envío', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create();
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
        $this->diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $this->clinica = Seccion::where('instrumento_id', $this->instrumento->id)
            ->where('orden', 3)->with('preguntas.items')->firstOrFail();
    });

    it('mantiene un único formulario con un paso y los inputs de cada pregunta', function () {
        $html = $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 3]))
            ->assertOk()
            ->assertSee('encuestaGuiada({ total: '.$this->clinica->preguntas->count().', inicial: 0 })', false)
            ->getContent();

        expect(substr_count($html, '<form method="POST"'))->toBe(2) // la sección + cerrar sesión
            ->and(substr_count($html, 'data-paso="'))->toBe($this->clinica->preguntas->count());

        foreach ($this->clinica->preguntas as $pregunta) {
            expect($html)->toContain("name=\"respuestas[{$pregunta->id}]");

            // Matrices: un grupo de radios por ítem, una sola vez (sin la tabla duplicada).
            foreach ($pregunta->items as $item) {
                $nombre = "name=\"respuestas[{$pregunta->id}][{$item->id}]\"";
                expect(substr_count($html, $nombre))->toBe(Opcion::where('pregunta_id', $pregunta->id)->count());
            }
        }
    });

    it('al reanudar abre la primera pregunta sin responder', function () {
        $primera = $this->clinica->preguntas->first();
        foreach ($primera->items as $item) {
            $opcion = Opcion::where('pregunta_id', $primera->id)->first();
            Respuesta::create([
                'diligenciamiento_id' => $this->diligenciamiento->id, 'pregunta_id' => $primera->id,
                'item_pregunta_id' => $item->id, 'opcion_id' => $opcion->id, 'valor_numerico' => $opcion->valor_numerico,
            ]);
        }

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 3]))
            ->assertOk()
            ->assertSee('inicial: 1 })', false);
    });

    it('tras un error de validación abre la pregunta con el error', function () {
        // Todo respondido salvo la tercera pregunta (P13).
        $payload = ['respuestas' => []];
        foreach ($this->clinica->preguntas as $i => $pregunta) {
            if ($i === 2) {
                continue;
            }
            $payload['respuestas'][$pregunta->id] = hu004ValorValido($pregunta);
        }

        $this->actingAs($this->estudiante)
            ->from(route('encuesta.seccion', [$this->diligenciamiento, 3]))
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 3]), $payload)
            ->assertRedirect(route('encuesta.seccion', [$this->diligenciamiento, 3]));

        $this->actingAs($this->estudiante)
            ->get(route('encuesta.seccion', [$this->diligenciamiento, 3]))
            ->assertSee('inicial: 2 })', false)
            ->assertSee('Revisa las preguntas marcadas');
    });

    it('la sección completa se sigue guardando en un solo envío', function () {
        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 3]), hu004PayloadSeccion($this->clinica))
            ->assertRedirect(route('encuesta.confirmar', $this->diligenciamiento));

        expect(Respuesta::where('diligenciamiento_id', $this->diligenciamiento->id)
            ->distinct()->count('pregunta_id'))->toBe($this->clinica->preguntas->count());
    });
});
