<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>UI Kit — GutGuardián</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500&family=IBM+Plex+Mono:wght@400&family=Source+Sans+3:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gg-papel text-gg-tinta font-sans antialiased">

{{-- ----------------------------------------------------------------
     Barra lateral de navegación del UI Kit
---------------------------------------------------------------- --}}
<div class="flex min-h-screen">

    <nav class="hidden lg:flex flex-col w-52 shrink-0 sticky top-0 h-screen bg-gg-superficie border-r border-gg-borde overflow-y-auto p-4 gap-0.5"
         aria-label="Secciones del UI Kit">
        <p class="text-2xs font-medium text-gg-tinta-suave uppercase tracking-wider px-2 mb-2">
            GutGuardián UI Kit
        </p>
        @foreach([
            ['#tokens',          'Tokens'],
            ['#tipografia',      'Tipografía'],
            ['#botones',         'Botones'],
            ['#campos',          'Campos de texto'],
            ['#alertas',         'Alertas'],
            ['#barra-progreso',  'Barra de progreso'],
            ['#escala-frecuencia','Escala frecuencia'],
            ['#escala-dolor',    'Escala dolor'],
            ['#matriz',          'Matriz'],
            ['#guiada',          'Encuesta guiada'],
            ['#multiple',        'Opción múltiple'],
            ['#pista-riesgo',    'Pista de riesgo'],
            ['#tarjeta',         'Tarjeta'],
            ['#marca',           'Marca / logo'],
            ['#insignia',        'Insignia riesgo'],
            ['#superficies',     'Superficies'],
            ['#aviso',           'Aviso no-diag.'],
        ] as [$href, $label])
        <a href="{{ $href }}" class="px-2 py-1.5 rounded text-xs text-gg-tinta-suave hover:text-gg-tinta hover:bg-gg-papel transition-colors">
            {{ $label }}
        </a>
        @endforeach
    </nav>

    {{-- Contenido principal --}}
    <main class="flex-1 max-w-3xl mx-auto px-4 py-10 space-y-20">

        {{-- Encabezado --}}
        <header>
            <h1 class="font-display text-3xl font-medium text-gg-tinta">UI Kit</h1>
            <p class="text-sm text-gg-tinta-suave mt-1">
                Sistema de diseño de GutGuardián · Sprint 0 · Evidencia de lineamientos de interfaz.
            </p>
        </header>

        {{-- ============================================================ --}}
        {{-- 1. TOKENS DE COLOR                                           --}}
        {{-- ============================================================ --}}
        <section id="tokens">
            <x-ui-kit-seccion titulo="Tokens de color" />

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @php
                $tokens = [
                    ['--gg-papel',           '#F6F7F4', 'Papel',           'bg-gg-papel'],
                    ['--gg-superficie',      '#FFFFFF',  'Superficie',      'bg-gg-superficie border border-gg-borde'],
                    ['--gg-tinta',           '#16302B', 'Tinta',           'bg-gg-tinta'],
                    ['--gg-tinta-suave',     '#55615B', 'Tinta suave',     'bg-gg-tinta-suave'],
                    ['--gg-borde',           '#E0E3DC', 'Borde',           'bg-gg-borde'],
                    ['--gg-primario',        '#1F5C4A', 'Primario',        'bg-gg-primario'],
                    ['--gg-primario-suave',  '#E6EFEA', 'Primario suave',  'bg-gg-primario-suave'],
                    ['--gg-primario-hondo',  '#174A3B', 'Primario hondo',  'bg-gg-primario-hondo'],
                    ['--gg-primario-noche',  '#0F2E25', 'Primario noche',  'bg-gg-primario-noche'],
                    ['--gg-acento',          '#CFE3B4', 'Acento (solo sobre verde)', 'bg-gg-acento'],
                    ['--gg-papel-hondo',     '#EDF0EA', 'Papel hondo',     'bg-gg-papel-hondo'],
                    ['--gg-riesgo-bajo',     '#3E7D64', 'Riesgo bajo',     'bg-gg-riesgo-bajo'],
                    ['--gg-riesgo-medio',    '#C08A2E', 'Riesgo medio',    'bg-gg-riesgo-medio'],
                    ['--gg-riesgo-alto',     '#9C4A32', 'Riesgo alto',     'bg-gg-riesgo-alto'],
                ];
                @endphp
                @foreach($tokens as [$var, $hex, $nombre, $bgClass])
                <div class="rounded-control overflow-hidden border border-gg-borde">
                    <div class="h-14 {{ $bgClass }}"></div>
                    <div class="p-2 bg-gg-superficie">
                        <p class="text-xs font-medium text-gg-tinta">{{ $nombre }}</p>
                        <p class="font-mono text-2xs text-gg-tinta-suave">{{ $hex }}</p>
                        <p class="font-mono text-2xs text-gg-tinta-suave">{{ $var }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 2. TIPOGRAFÍA                                                --}}
        {{-- ============================================================ --}}
        <section id="tipografia">
            <x-ui-kit-seccion titulo="Tipografía" />

            <div class="space-y-6">
                <div class="space-y-2">
                    <p class="text-2xs font-mono text-gg-tinta-suave">Bricolage Grotesque — solo títulos y cifras</p>
                    <p class="font-display text-3xl font-medium text-gg-tinta">Clasificación de riesgo</p>
                    <p class="font-display text-2xl font-medium text-gg-riesgo-medio">Riesgo medio</p>
                    <p class="font-display text-xl font-medium text-gg-tinta">Hábitos Nutricionales</p>
                </div>

                <div class="space-y-2">
                    <p class="text-2xs font-mono text-gg-tinta-suave">Source Sans 3 — cuerpo de texto</p>
                    <p class="text-base text-gg-tinta">¿Con qué frecuencia consume frutas?</p>
                    <p class="text-sm text-gg-tinta">Texto de tamaño estándar para preguntas y opciones del formulario.</p>
                    <p class="text-xs text-gg-tinta-suave">Texto secundario: equivalencias temporales, etiquetas de ayuda.</p>
                    <p class="text-2xs text-gg-tinta-suave">Texto mínimo 12 px: avisos legales, códigos, notas.</p>
                    <p class="text-sm italic text-gg-tinta-suave">Texto en cursiva para la opción "Ninguna" en selección múltiple.</p>
                </div>

                <div class="space-y-2">
                    <p class="text-2xs font-mono text-gg-tinta-suave">IBM Plex Mono — datos numéricos</p>
                    <p class="font-mono text-sm text-gg-tinta">2024-MED-0147</p>
                    <p class="font-mono text-sm text-gg-tinta">68.3 % · 24.1 % · 7.6 %</p>
                    <p class="font-mono text-sm text-gg-tinta">×2.3 · ×1.8 · ×1.4</p>
                </div>

                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-3">Escala tipográfica completa</p>
                    <div class="space-y-2">
                        @foreach([
                            ['text-3xl', '36px', 'Título principal'],
                            ['text-2xl', '28px', 'Título de resultado'],
                            ['text-xl',  '22px', 'Título de sección'],
                            ['text-md',  '17px', 'Subtítulo / topbar'],
                            ['text-base','16px', 'Cuerpo base'],
                            ['text-sm',  '14px', 'Preguntas y opciones'],
                            ['text-xs',  '13px', 'Etiquetas secundarias'],
                            ['text-2xs', '12px', 'Mínimo (avisos, mono)'],
                        ] as [$clase, $px, $uso])
                        <div class="flex items-baseline gap-4">
                            <span class="{{ $clase }} text-gg-tinta w-48 shrink-0">{{ $uso }}</span>
                            <span class="font-mono text-2xs text-gg-tinta-suave">{{ $px }} · {{ $clase }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 3. BOTONES                                                   --}}
        {{-- ============================================================ --}}
        <section id="botones">
            <x-ui-kit-seccion titulo="Botones" />

            <div class="space-y-6">
                @foreach(['primario', 'secundario', 'fantasma'] as $variante)
                <div>
                    <p class="text-xs font-mono text-gg-tinta-suave mb-3">variante="{{ $variante }}"</p>
                    <div class="flex flex-wrap gap-3 items-center">
                        <x-boton variante="{{ $variante }}">Acción normal</x-boton>
                        <x-boton variante="{{ $variante }}" tamano="sm">Tamaño sm</x-boton>
                        <x-boton variante="{{ $variante }}" :discapacitado="true">Deshabilitado</x-boton>
                        @if($variante === 'primario')
                        <x-boton variante="{{ $variante }}" tipo="submit">Enviar encuesta</x-boton>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 4. CAMPOS DE TEXTO                                           --}}
        {{-- ============================================================ --}}
        <section id="campos">
            <x-ui-kit-seccion titulo="Campos de texto" />

            <div class="space-y-4 max-w-sm">
                <x-campo-texto
                    nombre="email_ejemplo"
                    etiqueta="Correo electrónico"
                    tipo="email"
                    placeholder="nombre@umariana.edu.co"
                    autocomplete="email"
                />

                <x-campo-texto
                    nombre="pass_ejemplo"
                    etiqueta="Contraseña"
                    tipo="password"
                    :requerido="true"
                    ayuda="Mínimo 8 caracteres."
                />

                <x-campo-texto
                    nombre="error_ejemplo"
                    etiqueta="Campo con error"
                    tipo="text"
                    valor="valor incorrecto"
                    error="Este campo es requerido."
                />

                <x-campo-texto
                    nombre="disabled_ejemplo"
                    etiqueta="Campo deshabilitado"
                    tipo="text"
                    valor="Valor existente"
                    :discapacitado="true"
                />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 5. ALERTAS                                                   --}}
        {{-- ============================================================ --}}
        <section id="alertas">
            <x-ui-kit-seccion titulo="Alertas" />

            <div class="space-y-3">
                <x-alerta tipo="exito" titulo="Encuesta guardada">
                    Tus respuestas han sido guardadas correctamente.
                </x-alerta>

                <x-alerta tipo="error" titulo="Error de validación">
                    Hay preguntas sin responder en la sección de hábitos nutricionales.
                </x-alerta>

                <x-alerta tipo="aviso">
                    Esta sesión expirará en 10 minutos. Guarda tu progreso.
                </x-alerta>

                <x-alerta tipo="info" :cierre="true">
                    Recuerda que tus datos son confidenciales y solo serán usados para fines académicos.
                </x-alerta>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 6. BARRA DE PROGRESO                                         --}}
        {{-- ============================================================ --}}
        <section id="barra-progreso">
            <x-ui-kit-seccion titulo="Barra de progreso" />

            <div class="space-y-6 max-w-sm">
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-3">Inicio de sección 1</p>
                    <x-barra-progreso
                        :seccion-actual="1"
                        :total-secciones="3"
                        :pregunta-actual="1"
                        :total-preguntas="4"
                        nombre-seccion="Datos Sociodemográficos"
                    />
                </div>
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-3">Mitad de sección 2</p>
                    <x-barra-progreso
                        :seccion-actual="2"
                        :total-secciones="3"
                        :pregunta-actual="5"
                        :total-preguntas="10"
                        nombre-seccion="Hábitos Nutricionales"
                    />
                </div>
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-3">Final de sección 3</p>
                    <x-barra-progreso
                        :seccion-actual="3"
                        :total-secciones="3"
                        :pregunta-actual="10"
                        :total-preguntas="10"
                        nombre-seccion="Información Clínica"
                    />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 7. ESCALA DE FRECUENCIA                                      --}}
        {{-- ============================================================ --}}
        <section id="escala-frecuencia">
            <x-ui-kit-seccion titulo="Escala de frecuencia" />

            <div class="space-y-8">
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Sin seleccionar</p>
                    <x-escala-frecuencia
                        nombre="p01_ejemplo"
                        codigo="P01"
                        pregunta="¿Con qué frecuencia consume frutas?"
                    />
                </div>

                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Con valor pre-seleccionado (valor=2)</p>
                    <x-escala-frecuencia
                        nombre="p02_ejemplo"
                        codigo="P02"
                        pregunta="¿Con qué frecuencia consume ensaladas frescas?"
                        :valor="2"
                    />
                </div>

                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Con error de validación</p>
                    <x-escala-frecuencia
                        nombre="p03_ejemplo"
                        codigo="P03"
                        pregunta="¿Con qué frecuencia consume alimentos integrales?"
                        :requerido="true"
                        error="Esta pregunta es obligatoria."
                    />
                </div>

                <div class="p-4 bg-gg-superficie rounded-tarjeta border border-gg-borde">
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-1">Navegación por teclado</p>
                    <p class="text-xs text-gg-tinta-suave mb-4">
                        Tab para entrar al control · ← → para cambiar la selección · Tab para salir.
                    </p>
                    <x-escala-frecuencia
                        nombre="p04_teclado"
                        codigo="P04"
                        pregunta="Use las flechas del teclado para navegar entre las opciones."
                    />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 8. ESCALA DE DOLOR                                           --}}
        {{-- ============================================================ --}}
        <section id="escala-dolor">
            <x-ui-kit-seccion titulo="Escala de dolor (P13)" />

            <div class="space-y-8">
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Sin seleccionar</p>
                    <x-escala-dolor nombre="p13_ejemplo" />
                </div>
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Pre-seleccionado (valor=3)</p>
                    <x-escala-dolor nombre="p13_ejemplo2" :valor="3" />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 9. MATRIZ DE SÍNTOMAS                                        --}}
        {{-- ============================================================ --}}
        <section id="matriz">
            <x-ui-kit-seccion titulo="Matriz de síntomas / medicamentos" />

            @php
            $itemsSintomas = [
                ['id' => 1, 'etiqueta' => 'Diarrea'],
                ['id' => 2, 'etiqueta' => 'Dolor abdominal'],
                ['id' => 3, 'etiqueta' => 'Vómito'],
                ['id' => 4, 'etiqueta' => 'Náuseas'],
                ['id' => 5, 'etiqueta' => 'Estreñimiento'],
                ['id' => 6, 'etiqueta' => 'Fiebre'],
            ];

            $opcionesTemporalidad = [
                ['valor' => 5, 'etiqueta' => 'Última semana',   'equiv' => ''],
                ['valor' => 4, 'etiqueta' => 'Último mes',      'equiv' => ''],
                ['valor' => 3, 'etiqueta' => 'Últimos 2 meses', 'equiv' => ''],
                ['valor' => 2, 'etiqueta' => 'Últimos 6 meses', 'equiv' => ''],
                ['valor' => 1, 'etiqueta' => 'Último año',      'equiv' => ''],
                ['valor' => 0, 'etiqueta' => 'Nunca',           'equiv' => ''],
            ];

            $opcionesFrecuencia = [
                ['valor' => 3, 'etiqueta' => 'Siempre',         'equiv' => 'diario'],
                ['valor' => 2, 'etiqueta' => 'Algunas veces',   'equiv' => '1–6/sem'],
                ['valor' => 1, 'etiqueta' => 'Ocasionalmente',  'equiv' => '1+/mes'],
                ['valor' => 0, 'etiqueta' => 'Nunca',           'equiv' => '—'],
            ];
            @endphp

            <div class="space-y-10">
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">P11 — Temporalidad (acordeón en móvil)</p>
                    <x-matriz-sintomas
                        nombre="p11_ejemplo"
                        codigo="P11"
                        pregunta="¿En qué período ha presentado los siguientes síntomas gastrointestinales?"
                        :items="$itemsSintomas"
                        :opciones="$opcionesTemporalidad"
                    />
                </div>

                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">P12 — Frecuencia (4 opciones)</p>
                    <x-matriz-sintomas
                        nombre="p12_ejemplo"
                        codigo="P12"
                        pregunta="¿Con qué frecuencia ha presentado los siguientes síntomas en el último mes?"
                        :items="$itemsSintomas"
                        :opciones="$opcionesFrecuencia"
                    />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- ENCUESTA GUIADA (demo con la misma lógica encuestaGuiada)     --}}
        {{-- ============================================================ --}}
        <section id="guiada">
            <x-ui-kit-seccion titulo="Encuesta guiada (una pregunta por paso)" />
            <p class="text-sm text-gg-tinta-suave mb-4">
                Demo de la presentación de estudiante/encuesta/seccion: mismos componentes y mismo
                componente Alpine <span class="font-mono">encuestaGuiada</span>. Escala y selección única
                avanzan solas al tocar; múltiple y matriz con «Siguiente». Este formulario no guarda nada.
            </p>

            @php
            $demoSemestres = collect(range(1, 10))->map(fn ($n) => ['id' => 100 + $n, 'etiqueta' => "Semestre $n"])->all();
            $demoPasos = ['escala', 'single', 'multiple', 'matriz'];
            $demoMultiple = collect(['Parásitos intestinales', 'Inflamación del intestino', 'Infecciones bacterianas digestivas',
                'Úlceras digestivas', 'Estrés', 'Sobrepeso', 'Gastroenteritis', 'Hipersensibilidad visceral', 'Ninguna'])
                ->map(fn ($e, $k) => ['id' => 200 + $k, 'etiqueta' => $e, 'es_ninguna' => $e === 'Ninguna'])->all();
            @endphp

            <form method="GET" action="#guiada" novalidate
                  x-data="encuestaGuiada({ total: 4, inicial: 0 })"
                  @change="alCambiar($event)" @keyup="revisar()"
                  @keydown.enter="alEnter($event)" @submit="enviar($event)">
                <div x-ref="inicio" class="mb-4">
                    <p class="text-sm text-gg-tinta mb-2">Pregunta <span class="font-medium" x-text="paso + 1">1</span> de 4</p>
                    <div class="flex gap-1" aria-hidden="true">
                        @foreach($demoPasos as $i => $t)
                        <span class="flex-1 h-1.5 rounded-full transition-colors duration-300"
                              :class="paso === {{ $i }} ? 'bg-gg-primario' : (respondidas[{{ $i }}] ? 'bg-[#8DB3A2]' : 'bg-gg-borde')"></span>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-x-clip -mx-2 px-2 pb-2">
                @foreach($demoPasos as $i => $tipo)
                <section data-paso="{{ $i }}" data-tipo="{{ $tipo }}" x-show="paso === {{ $i }}"
                         @if($i !== 0) style="display: none" @endif
                         :class="direccion > 0 ? 'gg-paso-adelante' : 'gg-paso-atras'">
                    <x-tarjeta padding="p-5 sm:p-8" class="rounded-bloque shadow-elev-2"
                               x-bind:class="sacudir && paso === {{ $i }} && aviso ? 'gg-sacudir' : ''">
                        <p id="paso-{{ $i }}-titulo" tabindex="-1" class="text-sm text-gg-tinta-suave mb-3 focus:outline-none">Pregunta {{ $i + 1 }} de 4</p>
                        @if($tipo === 'escala')
                            <x-escala-frecuencia nombre="demo_p07" codigo="P07" pregunta="¿Con qué frecuencia consume frituras?" destacada />
                        @elseif($tipo === 'single')
                            <x-selector-unico nombre="demo_sd4" codigo="SD4" pregunta="¿En qué semestre se encuentra actualmente?" :opciones="$demoSemestres" destacada />
                        @elseif($tipo === 'multiple')
                            <x-opcion-multiple nombre="demo_p14" codigo="P14" pregunta="¿Ha padecido o le han diagnosticado alguna de las siguientes condiciones?" :opciones="$demoMultiple" destacada />
                        @else
                            <x-matriz-sintomas nombre="demo_p12" codigo="P12" pregunta="¿Con qué frecuencia ha presentado los siguientes síntomas en el último mes?"
                                               :items="$itemsSintomas" :opciones="$opcionesFrecuencia" destacada />
                        @endif
                        <p class="mt-4 text-sm font-medium text-gg-riesgo-alto" role="alert" x-show="aviso && paso === {{ $i }}" x-cloak x-text="aviso"></p>
                    </x-tarjeta>
                </section>
                @endforeach
                </div>

                <div class="mt-5 flex items-center gap-2 p-2 rounded-[18px] bg-gg-superficie border border-gg-borde shadow-elev-3">
                    <x-boton variante="fantasma" icono="atras" x-show="paso > 0" x-cloak x-on:click="anterior()">Anterior</x-boton>
                    <span class="flex-1"></span>
                    <x-boton variante="primario" icono-final="flecha" x-show="!esUltimo" x-on:click="siguiente()" data-demo="siguiente">Siguiente</x-boton>
                    <x-boton variante="primario" tipo="submit" icono-final="flecha" x-show="esUltimo" x-cloak x-bind:class="completo && 'gg-listo'">Siguiente sección</x-boton>
                </div>
            </form>
        </section>

        {{-- ============================================================ --}}
        {{-- 10. OPCIÓN MÚLTIPLE                                          --}}
        {{-- ============================================================ --}}
        <section id="multiple">
            <x-ui-kit-seccion titulo="Opción múltiple (P14, P15, P18, P20)" />

            @php
            $opcionesMultiple = [
                ['id' => 1, 'etiqueta' => 'Parásitos intestinales',            'es_ninguna' => false],
                ['id' => 2, 'etiqueta' => 'Inflamación del intestino',         'es_ninguna' => false],
                ['id' => 3, 'etiqueta' => 'Infecciones bacterianas digestivas','es_ninguna' => false],
                ['id' => 4, 'etiqueta' => 'Úlceras digestivas',               'es_ninguna' => false],
                ['id' => 5, 'etiqueta' => 'Estrés',                           'es_ninguna' => false],
                ['id' => 9, 'etiqueta' => 'Ninguna',                          'es_ninguna' => true],
            ];
            @endphp

            <div class="space-y-8">
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Sin seleccionar</p>
                    <x-opcion-multiple
                        nombre="p14_ejemplo"
                        codigo="P14"
                        pregunta="¿Ha padecido o le han diagnosticado alguna de las siguientes condiciones?"
                        :opciones="$opcionesMultiple"
                    />
                </div>
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Con selecciones previas (IDs 1 y 3)</p>
                    <x-opcion-multiple
                        nombre="p14_presel"
                        codigo="P14"
                        pregunta="¿Ha padecido o le han diagnosticado alguna de las siguientes condiciones?"
                        :opciones="$opcionesMultiple"
                        :seleccionados="[1, 3]"
                    />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 11. PISTA DE RIESGO                                          --}}
        {{-- ============================================================ --}}
        <section id="pista-riesgo">
            <x-ui-kit-seccion titulo="Pista de riesgo (resultado del modelo)" />

            @php
            $contribuciones = [
                ['etiqueta' => 'Consumo diario de frituras',      'odds' => 2.3],
                ['etiqueta' => 'Frecuencia baja de frutas',       'odds' => 1.8],
                ['etiqueta' => 'Estreñimiento en el último mes',  'odds' => 1.4],
            ];
            @endphp

            <div class="space-y-10">
                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Categoría 0 — Riesgo bajo</p>
                    <x-pista-riesgo
                        :categoria="0"
                        :prob-bajo="0.72"
                        :prob-medio="0.22"
                        :prob-alto="0.06"
                        :contribuciones="$contribuciones"
                        fecha="10 de agosto de 2026"
                        codigo-participante="2024-ENF-0042"
                    />
                </div>

                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Categoría 1 — Riesgo medio</p>
                    <x-pista-riesgo
                        :categoria="1"
                        :prob-bajo="0.24"
                        :prob-medio="0.61"
                        :prob-alto="0.15"
                        :contribuciones="$contribuciones"
                        fecha="10 de agosto de 2026"
                    />
                </div>

                <div>
                    <p class="text-2xs font-mono text-gg-tinta-suave mb-4">Categoría 2 — Riesgo alto</p>
                    <x-pista-riesgo
                        :categoria="2"
                        :prob-bajo="0.08"
                        :prob-medio="0.27"
                        :prob-alto="0.65"
                        :contribuciones="$contribuciones"
                        fecha="10 de agosto de 2026"
                    />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 12. TARJETA                                                  --}}
        {{-- ============================================================ --}}
        <section id="tarjeta">
            <x-ui-kit-seccion titulo="Tarjeta" />

            <div class="space-y-4">
                <x-tarjeta>
                    <p class="text-sm text-gg-tinta">Tarjeta estándar: padding p-5 (p-6 desde sm), elevación elev-1.</p>
                </x-tarjeta>

                <x-tarjeta padding="p-6">
                    <h3 class="font-display text-xl font-medium text-gg-tinta mb-2">Título dentro de tarjeta</h3>
                    <p class="text-sm text-gg-tinta-suave leading-relaxed">
                        Contenido secundario. Las tarjetas agrupan información relacionada.
                        Radio de esquina: 16 px. Borde: 1 px gg-borde. Sombra teñida de verde, nunca negra.
                    </p>
                </x-tarjeta>

                <x-tarjeta interactiva>
                    <p class="text-sm text-gg-tinta">Tarjeta <span class="font-mono">interactiva</span>: se eleva 2 px al pasar el cursor. Solo para tarjetas clicables.</p>
                </x-tarjeta>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- MARCA / ESPACIO DEL LOGO                                     --}}
        {{-- ============================================================ --}}
        <section id="marca">
            <x-ui-kit-seccion titulo="Marca y espacio del logo" />
            <p class="text-sm text-gg-tinta-suave mb-4">
                El recuadro punteado reserva el lugar del logo oficial. Al copiar el archivo en
                <span class="font-mono">public/img/logo.svg</span> (o .png) aparece en todas las pantallas sin tocar las vistas.
            </p>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-tarjeta class="flex flex-col gap-5">
                    <x-marca tamano="sm" />
                    <x-marca />
                    <x-marca tamano="lg" subtitulo="Universidad Mariana · Pasto" />
                </x-tarjeta>
                <div class="gg-marca rounded-tarjeta p-6 flex flex-col gap-5">
                    <x-marca tono="claro" tamano="sm" />
                    <x-marca tono="claro" />
                    <x-marca tono="claro" tamano="lg" subtitulo="Panel institucional" />
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- INSIGNIA DE RIESGO                                           --}}
        {{-- ============================================================ --}}
        <section id="insignia">
            <x-ui-kit-seccion titulo="Insignia de nivel de riesgo" />
            <p class="text-sm text-gg-tinta-suave mb-4">Punto de color + texto: el color nunca es el único portador del significado.</p>
            <div class="flex flex-wrap gap-3 mb-3">
                <x-insignia-riesgo :categoria="0" />
                <x-insignia-riesgo :categoria="1" />
                <x-insignia-riesgo :categoria="2" />
                <x-insignia-riesgo :categoria="null" />
            </div>
            <div class="flex flex-wrap gap-3">
                <x-insignia-riesgo :categoria="0" tamano="sm" />
                <x-insignia-riesgo :categoria="1" tamano="sm" />
                <x-insignia-riesgo :categoria="2" tamano="sm" />
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- SUPERFICIES                                                  --}}
        {{-- ============================================================ --}}
        <section id="superficies">
            <x-ui-kit-seccion titulo="Superficies de marca" />
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="gg-marca rounded-bloque p-6 min-h-[160px]">
                    <p class="font-mono text-xs text-white/70">.gg-marca</p>
                    <p class="mt-2 font-display text-xl font-medium">Verde profundo con patrón de flujo</p>
                    <p class="text-sm text-white/75 mt-1">Acceso, sidebar del panel y llamadas a la acción principales.</p>
                </div>
                <div class="gg-flujo-claro bg-gg-superficie border border-gg-borde rounded-bloque p-6 min-h-[160px] overflow-hidden">
                    <p class="font-mono text-xs text-gg-tinta-suave">.gg-flujo-claro</p>
                    <p class="mt-2 font-display text-xl font-medium text-gg-tinta">Patrón sutil sobre superficie</p>
                    <p class="text-sm text-gg-tinta-suave mt-1">Tarjetas protagonistas y estados vacíos.</p>
                </div>
            </div>
        </section>

        {{-- ============================================================ --}}
        {{-- 13. AVISO DE NO-DIAGNÓSTICO                                  --}}
        {{-- ============================================================ --}}
        <section id="aviso">
            <x-ui-kit-seccion titulo="Aviso de no-diagnóstico" />
            <p class="text-xs text-gg-tinta-suave mb-4">
                Componente obligatorio junto a todo resultado de riesgo. Texto validado por el equipo de Enfermería.
                No puede modificarse sin autorización del comité de ética. (Resolución 3100 de 2019)
            </p>
            <x-aviso-no-diagnostico />
        </section>

        {{-- Pie de página --}}
        <footer class="border-t border-gg-borde pt-8 pb-4">
            <p class="text-2xs text-gg-tinta-suave text-center">
                GutGuardián UI Kit · Sprint 0 · Solo entorno local · Universidad Mariana, Pasto.
            </p>
        </footer>

    </main>
</div>

</body>
</html>

{{-- Componente interno de sección para el UI Kit --}}
@once
@push('styles')
@endpush
@endonce
