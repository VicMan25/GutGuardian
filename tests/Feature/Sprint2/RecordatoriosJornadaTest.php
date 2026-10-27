<?php

use App\Models\User;
use App\Modules\Auth\Models\Consentimiento;
use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Instrumento;
use App\Modules\Encuestas\Models\JornadaDiligenciamiento;
use App\Modules\Encuestas\Notifications\RecordatorioJornada;
use Database\Seeders\InstrumentoSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * Recordatorio por correo de la encuesta por jornadas
 * (gutguardian:enviar-recordatorios, programado una vez al día).
 */
describe('Recordatorios de la encuesta por jornadas', function () {

    beforeEach(function () {
        $this->seed([RolesPermisosSeeder::class, InstrumentoSeeder::class]);
        Notification::fake();

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

        // Día 1 respondido por la ruta real.
        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 1]), encuestaPayloadJornada($this->instrumento->id, 1));
    });

    it('al día siguiente envía un único recordatorio del día pendiente', function () {
        $this->travel(1)->days();

        $this->artisan('gutguardian:enviar-recordatorios')->assertSuccessful();
        Notification::assertSentTo($this->estudiante, RecordatorioJornada::class, fn ($n) => $n->jornada === 2 && $n->total === 3);

        $this->artisan('gutguardian:enviar-recordatorios')->expectsOutput('No hay recordatorios pendientes hoy.');
        Notification::assertSentTimes(RecordatorioJornada::class, 1);
    });

    it('no envía el mismo día en que se terminó el anterior', function () {
        $this->artisan('gutguardian:enviar-recordatorios')->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('vuelve a recordar el día siguiente al avanzar, una vez por día pendiente', function () {
        $this->travel(1)->days();
        $this->artisan('gutguardian:enviar-recordatorios');

        $this->actingAs($this->estudiante)
            ->post(route('encuesta.guardar', [$this->diligenciamiento, 2]), encuestaPayloadJornada($this->instrumento->id, 2));
        $this->travel(1)->days();
        $this->artisan('gutguardian:enviar-recordatorios');

        Notification::assertSentTo($this->estudiante, RecordatorioJornada::class, fn ($n) => $n->jornada === 3);
        Notification::assertSentTimes(RecordatorioJornada::class, 2);
    });

    it('no escribe a quien desactivó los recordatorios en su perfil', function () {
        $this->actingAs($this->estudiante)
            ->put(route('perfil.recordatorios'), [])
            ->assertRedirect(route('perfil.edit'));

        expect($this->estudiante->fresh()->recibir_recordatorios)->toBeFalse();

        $this->travel(1)->days();
        $this->artisan('gutguardian:enviar-recordatorios');

        Notification::assertNothingSent();
    });

    it('no escribe a cuentas desactivadas ni por ciclos ya completados', function () {
        $this->estudiante->update(['activo' => false]);
        $otro = User::factory()->create();
        $otro->assignRole('estudiante');
        $cerrado = Diligenciamiento::create([
            'user_id' => $otro->id, 'instrumento_id' => $this->instrumento->id,
            'estado' => 'completado', 'completado_at' => now(),
        ]);
        JornadaDiligenciamiento::create(['diligenciamiento_id' => $cerrado->id, 'jornada' => 1, 'completada_at' => now()]);

        $this->travel(1)->days();
        $this->artisan('gutguardian:enviar-recordatorios');

        Notification::assertNothingSent();
    });

    it('--simular lista los destinatarios sin enviar ni marcar nada', function () {
        $this->travel(1)->days();

        $this->artisan('gutguardian:enviar-recordatorios --simular')
            ->expectsOutputToContain('día 2 de 3')
            ->assertSuccessful();

        Notification::assertNothingSent();
        expect(JornadaDiligenciamiento::whereNotNull('recordatorio_enviado_at')->exists())->toBeFalse();
    });

    it('el correo avisa y enlaza la encuesta sin datos clínicos', function () {
        $correo = (new RecordatorioJornada(2, 3))->toMail($this->estudiante);
        $texto = implode(' ', [$correo->subject, $correo->greeting, ...$correo->introLines, ...$correo->outroLines]);

        expect($correo->subject)->toContain('día 2')
            ->and($correo->greeting)->toBe('Hola, Ana')
            ->and($correo->actionUrl)->toBe(route('encuesta.iniciar'))
            ->and(mb_strtolower($texto))->not->toContain('riesgo')
            ->and(mb_strtolower($texto))->not->toContain('síntoma');
    });

    it('el comando queda programado una vez al día', function () {
        $this->artisan('schedule:list')->expectsOutputToContain('gutguardian:enviar-recordatorios');
    });
});
