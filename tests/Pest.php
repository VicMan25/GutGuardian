<?php

use App\Modules\Encuestas\Models\Diligenciamiento;
use App\Modules\Encuestas\Models\Opcion;
use App\Modules\Encuestas\Models\Pregunta;
use App\Modules\Encuestas\Services\JornadaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Valor de envío VÁLIDO para cualquier pregunta de la encuesta, en el mismo
 * formato que enviaría el formulario (ver EncuestaController::reglasJornada).
 * `$offset` elige otra opción (por orden) para escalas, únicas y matrices;
 * las múltiples siempre marcan «Ninguna».
 */
function encuestaValorValido(Pregunta $pregunta, int $offset = 0): int|array
{
    $opcion = fn () => Opcion::where('pregunta_id', $pregunta->id)->orderBy('orden')->get()[$offset];

    return match ($pregunta->tipo) {
        'escala' => $opcion()->valor_numerico,
        'single' => $opcion()->id,
        'multiple' => [
            (Opcion::where('pregunta_id', $pregunta->id)->where('etiqueta', 'Ninguna')->first()
                ?? Opcion::where('pregunta_id', $pregunta->id)->first())->id,
        ],
        'matriz' => $pregunta->items->mapWithKeys(fn ($item) => [$item->id => $opcion()->valor_numerico])->all(),
    };
}

/** Payload completo y válido de una jornada (día) de la encuesta. */
function encuestaPayloadJornada(int $instrumentoId, int $jornada, int $offset = 0): array
{
    $respuestas = [];
    foreach (app(JornadaService::class)->preguntas($instrumentoId, $jornada) as $pregunta) {
        $respuestas[$pregunta->id] = encuestaValorValido($pregunta, $offset);
    }

    return ['respuestas' => $respuestas];
}

/** Guarda todas las jornadas del diligenciamiento a través de la ruta real. */
function encuestaCompletarCiclo($actor, Diligenciamiento $diligenciamiento): void
{
    $total = app(JornadaService::class)->total($diligenciamiento->instrumento_id);

    for ($jornada = 1; $jornada <= $total; $jornada++) {
        $actor->post(
            route('encuesta.guardar', [$diligenciamiento, $jornada]),
            encuestaPayloadJornada($diligenciamiento->instrumento_id, $jornada),
        );
    }
}
