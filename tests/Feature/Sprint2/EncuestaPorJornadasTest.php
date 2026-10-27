<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\JornadaDiligenciamiento;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Services\JornadaService;
use App\Modules\Reportes\Services\UsabilidadService;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;

/**
 * Encuesta por jornadas: el instrumento completo se responde en 3 días con
 * desbloqueo flexible, sin vencimiento y con ciclos ilimitados.
 */
describe('Encuesta por jornadas — reparto, avance flexible y medición', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);

        $this->estudiante = User::factory()->create(['name' => 'Ana Pérez']);
        $this->estudiante->assignRole('estudiante');
        Consentimiento::create([
            'user_id' => $this->estudiante->id, 'version_politica' => '1.0',
            'aceptado_at' => now(), 'ip' => '127.0.0.1',
        ]);

        $this->instrumento = Instrumento::where('activo', true)->firstOrFail();
        $this->diligenciamiento = Diligenciamiento::create([
            'user_id' => $this->estudiante->id, 'instrumento_id' => $this->instrumento->id, 'estado' => 'pendiente',
        ]);
        $this->jornadas = app(JornadaService::class);
    });

    it('reparte todo el instrumento en 3 días, sin preguntas fuera de reparto', function () {
        $porJornada = Pregunta::all()->groupBy('jornada');

        expect($this->jornadas->total($this->instrumento->id))->toBe(3)
            ->and($porJornada->keys()->sort()->values()->all())->toBe([1, 2, 3])
            ->and($porJornada->flatten()->count())->toBe(24); // SD1–SD4 + P01–P20
    });

    it('mantiene juntas las preguntas que comparten ítems y las cuatro que definen Y', function () {
        $jornada = fn (string $codigo) => Pregunta::where('codigo', $codigo)->value('jornada');

        expect($jornada('P11'))->toBe($jornada('P12'))
            ->and($jornada('P12'))->toBe($jornada('P13'))
            ->and($jornada('P13'))->toBe($jornada('P20'))
            ->and($jornada('P16'))->toBe($jornada('P17'))
            ->and($jornada('P14'))->toBe($jornada('P15'))
            ->and($jornada('P18'))->toBe($jornada('P19'));
    });

    it('al terminar un día muestra «listo por hoy» y permite seguir de inmediato (desbloqueo flexible)', function () {
        $actor = $this->actingAs($this->estudiante);
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));

        $actor->get(route('encuesta.pausa', $this->diligenciamiento))
            ->assertOk()
            ->assertSee('¡Listo por hoy!')
            ->assertSee('estará disponible desde mañana')
            ->assertSee('Seguir con el día 2');

        $actor->get(route('encuesta.jornada', [$this->diligenciamiento, 2]))->assertOk();
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 2]), encuestaPayloadJornada($this->instrumento->id, 2))
            ->assertRedirect(route('encuesta.pausa', $this->diligenciamiento));
    });

    it('el día siguiente se sugiere desde la medianoche de Colombia posterior a terminar el anterior', function () {
        // 23:30 en Bogotá (04:30 UTC del día siguiente).
        $this->travelTo(now()->setTimezone(JornadaService::ZONA)->setTime(23, 30));
        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));

        $sugerida = $this->jornadas->sugeridaDesde($this->diligenciamiento->fresh('jornadas'), 2);

        expect($sugerida->toDateTimeString())->toBe(now()->setTimezone(JornadaService::ZONA)->addDay()->startOfDay()->toDateTimeString())
            ->and($sugerida->isFuture())->toBeTrue();

        $this->travel(31)->minutes();
        expect($sugerida->isPast())->toBeTrue();
    });

    it('/inicio muestra el avance del ciclo abierto y cuándo sigue', function () {
        $actor = $this->actingAs($this->estudiante);
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));

        $actor->get(route('estudiante.inicio'))
            ->assertSee('Vas en el día 2 de 3')
            ->assertSee('1 de 3 días completados')
            ->assertSee('se sugiere desde mañana')
            ->assertSee('Continuar con el día 2');

        $this->travel(1)->days();

        $actor->get(route('estudiante.inicio'))
            ->assertSee('La parte de hoy ya está disponible.');
    });

    it('el ciclo no vence: semanas después se retoma en el día pendiente', function () {
        $actor = $this->actingAs($this->estudiante);
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));

        $this->travel(60)->days();

        $actor->get(route('encuesta.iniciar'))
            ->assertRedirect(route('encuesta.jornada', [$this->diligenciamiento, 2]));
        expect(Diligenciamiento::where('user_id', $this->estudiante->id)->count())->toBe(1);
    });

    it('al cerrar un ciclo se puede iniciar otro de inmediato, sin límite', function () {
        $actor = $this->actingAs($this->estudiante);

        foreach (range(1, 2) as $_) {
            $abierto = Diligenciamiento::where('user_id', $this->estudiante->id)->where('estado', '!=', 'completado')->first();
            encuestaCompletarCiclo($actor, $abierto);
            $actor->post(route('encuesta.finalizar', $abierto));
            $actor->get(route('encuesta.iniciar'));
        }

        expect(Diligenciamiento::where('user_id', $this->estudiante->id)->where('estado', 'completado')->count())->toBe(2)
            ->and(Diligenciamiento::where('user_id', $this->estudiante->id)->where('estado', 'pendiente')->count())->toBe(1);
    });

    it('volver a guardar un día ya completado no mueve su fecha de completado', function () {
        $actor = $this->actingAs($this->estudiante);
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));
        $primera = JornadaDiligenciamiento::where('jornada', 1)->first()->completada_at;

        $this->travel(2)->hours();
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1, 1));

        expect(JornadaDiligenciamiento::where('jornada', 1)->first()->completada_at->equalTo($primera))->toBeTrue();
    });

    it('una jornada abandonada reinicia su hora de inicio al volver', function () {
        $actor = $this->actingAs($this->estudiante);
        $actor->get(route('encuesta.jornada', [$this->diligenciamiento, 1]));

        $this->travel(3)->days();
        $actor->get(route('encuesta.jornada', [$this->diligenciamiento, 1]));

        expect(JornadaDiligenciamiento::where('jornada', 1)->first()->iniciada_at->diffInMinutes(now()))->toBeLessThan(1);
    });

    it('el tiempo de diligenciamiento suma el tiempo activo de cada día, no los días transcurridos', function () {
        $this->diligenciamiento->update(['estado' => 'completado', 'completado_at' => now()->addDays(3)]);

        foreach ([1 => 10, 2 => 5, 3 => 6] as $jornada => $minutos) {
            $inicio = now()->addDays($jornada - 1);
            JornadaDiligenciamiento::create([
                'diligenciamiento_id' => $this->diligenciamiento->id, 'jornada' => $jornada,
                'iniciada_at' => $inicio, 'completada_at' => $inicio->copy()->addMinutes($minutos),
            ]);
        }

        expect(app(UsabilidadService::class)->metricasInteraccion()['tiempo']['mediana'])->toBe(21.0);
    });

    it('los envíos por día cuentan para la tasa de errores', function () {
        $actor = $this->actingAs($this->estudiante);
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), ['respuestas' => []]);
        $actor->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));

        expect(app(UsabilidadService::class)->metricasInteraccion()['errores'])
            ->toMatchArray(['envios' => 2, 'con_error' => 1, 'tasa' => 50.0]);
    });
});
