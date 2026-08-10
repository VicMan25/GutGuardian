<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InstrumentoSeeder extends Seeder
{
    private Carbon $ahora;

    public function run(): void
    {
        if (DB::table('instrumentos')->where('version', '1.0')->exists()) {
            $this->command->info('InstrumentoSeeder: instrumento v1.0 ya existe, se omite.');
            return;
        }

        $this->ahora = Carbon::now();

        $instrumentoId = $this->crearInstrumento();
        [$sdId, $nutId, $clinId] = $this->crearSecciones($instrumentoId);

        $this->sembrarSociodemografico($sdId);
        $this->sembrarNutricional($nutId);
        $this->sembrarClinica($clinId);
    }

    // ----------------------------------------------------------------
    // Estructura principal
    // ----------------------------------------------------------------

    private function crearInstrumento(): int
    {
        return DB::table('instrumentos')->insertGetId([
            'nombre'     => 'Instrumento de Monitoreo de Hábitos Alimentarios y Sintomatología Gastrointestinal',
            'version'    => '1.0',
            'tipo'       => 'mixto',
            'activo'     => true,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);
    }

    private function crearSecciones(int $instrumentoId): array
    {
        $secciones = [
            ['nombre' => 'Datos Sociodemográficos', 'orden' => 1],
            ['nombre' => 'Hábitos Nutricionales',   'orden' => 2],
            ['nombre' => 'Información Clínica',     'orden' => 3],
        ];

        $ids = [];
        foreach ($secciones as $s) {
            $ids[] = DB::table('secciones')->insertGetId(array_merge($s, [
                'instrumento_id' => $instrumentoId,
                'created_at'     => $this->ahora,
                'updated_at'     => $this->ahora,
            ]));
        }

        return $ids;
    }

    // ----------------------------------------------------------------
    // Helpers de inserción
    // ----------------------------------------------------------------

    private function pregunta(int $seccionId, string $codigo, string $enunciado, string $tipo, int $orden): int
    {
        return DB::table('preguntas')->insertGetId([
            'seccion_id' => $seccionId,
            'codigo'     => $codigo,
            'enunciado'  => $enunciado,
            'tipo'       => $tipo,
            'orden'      => $orden,
            'created_at' => $this->ahora,
            'updated_at' => $this->ahora,
        ]);
    }

    /** @param array<int, array{0: string, 1: int}> $opciones */
    private function opciones(int $preguntaId, array $opciones): void
    {
        foreach ($opciones as $orden => $opcion) {
            DB::table('opciones')->insert([
                'pregunta_id'    => $preguntaId,
                'etiqueta'       => $opcion[0],
                'valor_numerico' => $opcion[1],
                'orden'          => $orden + 1,
                'created_at'     => $this->ahora,
                'updated_at'     => $this->ahora,
            ]);
        }
    }

    /** @param string[] $etiquetas */
    private function items(int $preguntaId, array $etiquetas): void
    {
        foreach ($etiquetas as $orden => $etiqueta) {
            DB::table('items_pregunta')->insert([
                'pregunta_id' => $preguntaId,
                'etiqueta'    => $etiqueta,
                'orden'       => $orden + 1,
                'created_at'  => $this->ahora,
                'updated_at'  => $this->ahora,
            ]);
        }
    }

    // Siempre=3 … Nunca=0 — escala ordinal de 4 niveles estándar del instrumento
    private function opcionesEscalaFrecuencia(int $preguntaId): void
    {
        $this->opciones($preguntaId, [
            ['Siempre (diario)',                        3],
            ['Algunas veces (1 a 6 veces por semana)',  2],
            ['Ocasionalmente (1 o más veces al mes)',   1],
            ['Nunca',                                   0],
        ]);
    }

    // Más reciente = valor más alto, refleja mayor exposición reciente al factor de riesgo.
    // Criterio clínico: Última semana=5 … Nunca=0.
    private function opcionesTemporalidad(int $preguntaId): void
    {
        $this->opciones($preguntaId, [
            ['Última semana',    5],
            ['Último mes',       4],
            ['Últimos 2 meses',  3],
            ['Últimos 6 meses',  2],
            ['Último año',       1],
            ['Nunca',            0],
        ]);
    }

    // ----------------------------------------------------------------
    // Sección 1: Datos Sociodemográficos
    // ----------------------------------------------------------------

    private function sembrarSociodemografico(int $seccionId): void
    {
        // SD1 — Género: nominal 1-3; se dummy-encodeará en el modelo multinomial
        $sd1 = $this->pregunta($seccionId, 'SD1', '¿Cuál es su género?', 'single', 1);
        $this->opciones($sd1, [
            ['Masculino',        1],
            ['Femenino',         2],
            ['No binario / Otro', 3],
        ]);

        // SD2 — Edad: rangos ordinales 1-5 para reducir variabilidad y facilitar la codificación
        $sd2 = $this->pregunta($seccionId, 'SD2', '¿Cuál es su rango de edad?', 'single', 2);
        $this->opciones($sd2, [
            ['17 a 18 años',  1],
            ['19 a 20 años',  2],
            ['21 a 22 años',  3],
            ['23 a 25 años',  4],
            ['26 años o más', 5],
        ]);

        // SD3 — Programa: nominal; se dummy-encodeará en el modelo
        $sd3 = $this->pregunta($seccionId, 'SD3', '¿En qué programa académico está matriculado(a)?', 'single', 3);
        $this->opciones($sd3, [
            ['Enfermería',                          1],
            ['Nutrición y Dietética',               2],
            ['Medicina',                            3],
            ['Ingeniería de Sistemas',              4],
            ['Psicología',                          5],
            ['Bacteriología y Laboratorio Clínico', 6],
            ['Fisioterapia',                        7],
            ['Otro',                                8],
        ]);

        // SD4 — Semestre: ordinal = número real del semestre (1-10)
        $sd4 = $this->pregunta($seccionId, 'SD4', '¿En qué semestre se encuentra actualmente?', 'single', 4);
        $this->opciones($sd4, array_map(fn ($s) => ["Semestre $s", $s], range(1, 10)));
    }

    // ----------------------------------------------------------------
    // Sección 2: Hábitos Nutricionales (P01-P10)
    // ----------------------------------------------------------------

    private function sembrarNutricional(int $seccionId): void
    {
        // P01-P07: escala de frecuencia estándar Siempre=3 … Nunca=0
        $escalasSimples = [
            1 => ['P01', '¿Con qué frecuencia consume frutas?'],
            2 => ['P02', '¿Con qué frecuencia consume ensaladas frescas?'],
            3 => ['P03', '¿Con qué frecuencia consume alimentos integrales (pan, arroz, avena integral)?'],
            4 => ['P04', '¿Con qué frecuencia consume embutidos (salchicha, chorizo, salchichón)?'],
            5 => ['P05', '¿Con qué frecuencia consume alimentos empaquetados (snacks, papas de bolsa)?'],
            6 => ['P06', '¿Con qué frecuencia consume azúcares refinados (chocolates, dulces, gaseosas)?'],
            7 => ['P07', '¿Con qué frecuencia consume frituras?'],
        ];

        foreach ($escalasSimples as $orden => [$codigo, $enunciado]) {
            $pid = $this->pregunta($seccionId, $codigo, $enunciado, 'escala', $orden);
            $this->opcionesEscalaFrecuencia($pid);
        }

        // P08 — Tiempos de comida al día: ordinal 0 (ninguna) a 6 (más de 5).
        // El 0 refleja ausencia total de estructura alimentaria, considerado el mayor riesgo nutricional.
        $p08 = $this->pregunta($seccionId, 'P08', '¿Cuántos tiempos de comida realiza al día?', 'single', 8);
        $this->opciones($p08, [
            ['Ninguna',    0],
            ['1 tiempo',   1],
            ['2 tiempos',  2],
            ['3 tiempos',  3],
            ['4 tiempos',  4],
            ['5 tiempos',  5],
            ['Más de 5',   6],
        ]);

        // P09 — Consumo de alcohol y tabaco: matriz 2 ítems × escala de 4 niveles
        $p09 = $this->pregunta($seccionId, 'P09',
            '¿Con qué frecuencia consume las siguientes sustancias?', 'matriz', 9);
        $this->items($p09, ['Alcohol', 'Tabaco']);
        $this->opcionesEscalaFrecuencia($p09);

        // P10 — Lavado de manos antes de comer
        $p10 = $this->pregunta($seccionId, 'P10',
            '¿Con qué frecuencia se lava las manos antes de consumir alimentos?', 'escala', 10);
        $this->opcionesEscalaFrecuencia($p10);
    }

    // ----------------------------------------------------------------
    // Sección 3: Información Clínica (P11-P20)
    // ----------------------------------------------------------------

    private function sembrarClinica(int $seccionId): void
    {
        $sintomasItems = [
            'Diarrea', 'Dolor abdominal', 'Vómito', 'Náuseas', 'Estreñimiento', 'Fiebre',
        ];

        $medicamentosItems = [
            'Antidepresivos', 'Antiinflamatorios', 'Antibióticos',
            'Antiespasmódicos', 'Antidiarreicos', 'Laxantes', 'Corticosteroides',
        ];

        // P11 — Temporalidad de síntomas: matriz 6 síntomas × 6 opciones temporales
        $p11 = $this->pregunta($seccionId, 'P11',
            '¿En qué período ha presentado los siguientes síntomas gastrointestinales?', 'matriz', 1);
        $this->items($p11, $sintomasItems);
        $this->opcionesTemporalidad($p11);

        // P12 — Frecuencia de síntomas en el último mes: matriz 6 síntomas × escala 4 niveles
        $p12 = $this->pregunta($seccionId, 'P12',
            '¿Con qué frecuencia ha presentado los siguientes síntomas en el último mes?', 'matriz', 2);
        $this->items($p12, $sintomasItems);
        $this->opcionesEscalaFrecuencia($p12);

        // P13 — Escala de dolor abdominal 1-5: ordinal directo (1 = sin dolor, 5 = muy intenso)
        $p13 = $this->pregunta($seccionId, 'P13',
            '¿Cómo califica la intensidad del dolor abdominal presentado en los últimos 6 meses? (1 = sin dolor  —  5 = muy intenso)',
            'escala', 3);
        $this->opciones($p13, [
            ['1 — Sin dolor',         1],
            ['2 — Dolor leve',        2],
            ['3 — Dolor moderado',    3],
            ['4 — Dolor intenso',     4],
            ['5 — Dolor muy intenso', 5],
        ]);

        // P14/P15 — Antecedentes personales y familiares (selección múltiple).
        // Cada condición tiene valor_numerico=1 (indicador binario para el modelo logístico).
        // La opción "Ninguna" tiene valor_numerico=0 y es mutuamente excluyente con las demás.
        $antecedentes = [
            ['Parásitos intestinales',             1],
            ['Inflamación del intestino',          1],
            ['Infecciones bacterianas digestivas', 1],
            ['Úlceras digestivas',                 1],
            ['Estrés',                             1],
            ['Sobrepeso',                          1],
            ['Gastroenteritis',                    1],
            ['Hipersensibilidad visceral',         1],
            ['Ninguna',                            0],
        ];

        $p14 = $this->pregunta($seccionId, 'P14',
            '¿Ha padecido o le han diagnosticado alguna de las siguientes condiciones? (Puede seleccionar varias)',
            'multiple', 4);
        $this->opciones($p14, $antecedentes);

        $p15 = $this->pregunta($seccionId, 'P15',
            '¿Algún familiar directo ha padecido alguna de las siguientes condiciones? (Puede seleccionar varias)',
            'multiple', 5);
        $this->opciones($p15, $antecedentes);

        // P16 — Temporalidad de medicamentos: matriz 7 medicamentos × 6 opciones temporales
        $p16 = $this->pregunta($seccionId, 'P16',
            '¿En qué período ha consumido los siguientes medicamentos?', 'matriz', 6);
        $this->items($p16, $medicamentosItems);
        $this->opcionesTemporalidad($p16);

        // P17 — Frecuencia de medicamentos en el último mes: matriz 7 medicamentos × escala 4 niveles
        $p17 = $this->pregunta($seccionId, 'P17',
            '¿Con qué frecuencia ha consumido los siguientes medicamentos en el último mes?', 'matriz', 7);
        $this->items($p17, $medicamentosItems);
        $this->opcionesEscalaFrecuencia($p17);

        // P18 — Estilo de vida: selección múltiple; valor_numerico=1 por factor (indicador binario)
        $p18 = $this->pregunta($seccionId, 'P18',
            '¿Cuáles de los siguientes aspectos describen su estilo de vida actual? (Puede seleccionar varias)',
            'multiple', 8);
        $this->opciones($p18, [
            ['Sobrecarga académica', 1],
            ['Estrés psicológico',   1],
            ['Sedentarismo',         1],
            ['Practica deporte',     1],
            ['Ninguna',              0],
        ]);

        // P19 — Frecuencia de práctica deportiva
        $p19 = $this->pregunta($seccionId, 'P19',
            '¿Con qué frecuencia practica actividad física o deporte?', 'escala', 9);
        $this->opcionesEscalaFrecuencia($p19);

        // P20 — Enfermedades padecidas: selección múltiple; valor_numerico=1 por enfermedad
        $p20 = $this->pregunta($seccionId, 'P20',
            '¿Ha sido diagnosticado(a) con alguna de las siguientes enfermedades? (Puede seleccionar varias)',
            'multiple', 10);
        $this->opciones($p20, [
            ['Reflujo gastroesofágico',          1],
            ['Colitis',                          1],
            ['Enfermedad de Crohn',              1],
            ['Colecistitis',                     1],
            ['Enfermedad celíaca',               1],
            ['Apendicitis',                      1],
            ['Cálculos (vesiculares o renales)', 1],
            ['Anemia',                           1],
            ['Ninguna',                          0],
        ]);
    }
}
