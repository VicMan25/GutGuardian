<x-layouts.estudiante titulo="Inicio" ancho="amplio">

    @php
        $primerNombre = \Illuminate\Support\Str::of(Auth::user()->name)->trim()->explode(' ')->first();
    @endphp

    <div class="gg-entrada space-y-6">

    {{-- Saludo --}}
    <div class="pt-2">
        <p class="gg-rotulo">{{ ucfirst(now()->locale('es')->translatedFormat('l j \d\e F')) }}</p>
        <h1 class="mt-1 font-display text-3xl sm:text-4xl font-medium text-gg-tinta">
            Hola, {{ $primerNombre }}
        </h1>
        <p class="text-base text-gg-tinta-suave mt-2 max-w-xl">
            Aquí encontrarás tu historial de encuestas y tu nivel de riesgo digestivo.
        </p>
    </div>

    @if($totalCompletados === 0)
        {{-- Estado vacío: primera encuesta como protagonista --}}
        <section class="gg-marca rounded-bloque overflow-hidden shadow-elev-3">
            <div class="grid lg:grid-cols-[1.1fr_1fr] gap-8 p-6 sm:p-10">
                <div>
                    <span class="inline-flex items-center gap-2 text-sm text-gg-acento">
                        <x-icono nombre="hoja" class="w-4 h-4" />
                        Tu punto de partida
                    </span>
                    <h2 class="mt-3 font-display text-2xl sm:text-3xl font-medium text-white">
                        Aún no tienes registros
                    </h2>
                    <p class="mt-3 text-base text-white/80 max-w-md leading-relaxed">
                        Completa tu primera encuesta para conocer tu nivel de riesgo digestivo
                        y recibir orientación de autocuidado.
                    </p>
                    <div class="mt-7">
                        <x-boton variante="claro" tamano="lg" href="{{ route('encuesta.iniciar') }}" icono-final="flecha">
                            Comenzar encuesta
                        </x-boton>
                    </div>
                </div>

                <ol class="grid gap-3 self-center" role="list">
                    @foreach([
                        ['n' => '1', 'titulo' => 'Responde tres secciones', 'texto' => 'Datos generales, hábitos alimentarios y síntomas. Puedes pausar y continuar después.'],
                        ['n' => '2', 'titulo' => 'Conoce tu nivel de riesgo', 'texto' => 'Bajo, medio o alto, con los factores que más influyeron.'],
                        ['n' => '3', 'titulo' => 'Sigue tu evolución', 'texto' => 'Repite la encuesta y compara tus resultados en el tiempo.'],
                    ] as $paso)
                    <li class="flex gap-4 p-4 rounded-tarjeta bg-white/[0.07] border border-white/10">
                        <span class="shrink-0 w-8 h-8 rounded-full bg-gg-acento text-gg-primario-noche font-mono text-sm inline-flex items-center justify-center">{{ $paso['n'] }}</span>
                        <div>
                            <p class="text-base font-medium text-white">{{ $paso['titulo'] }}</p>
                            <p class="text-sm text-white/70 mt-0.5 leading-snug">{{ $paso['texto'] }}</p>
                        </div>
                    </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @else
        @php
        $etiquetasCategoria = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
        $bgHex   = ['#3E7D64', '#C08A2E', '#9C4A32'];
        $tinta   = ['#2F664F', '#8A6116', '#86402B'];
        $bgSuave = ['#E8F3EE', '#FBF3E2', '#F5EBE8'];
        $evaluacion = $ultimoDiligenciamiento->evaluaciones->first();
        @endphp

        <div class="grid gap-5 lg:grid-cols-[1.35fr_1fr]">

            {{-- Resultado más reciente — la tarjeta protagonista --}}
            <section class="relative overflow-hidden bg-gg-superficie border border-gg-borde rounded-bloque shadow-elev-2 gg-flujo-claro">
                <div class="h-1.5" style="background-color: {{ $evaluacion ? $bgHex[$evaluacion->categoria] : '#E0E3DC' }};" aria-hidden="true"></div>
                <div class="p-6 sm:p-8">
                    <p class="text-sm text-gg-tinta-suave">Tu resultado más reciente</p>
                    @if($evaluacion)
                        <p class="mt-1 font-display text-3xl sm:text-4xl font-medium" style="color: {{ $tinta[$evaluacion->categoria] }}">
                            {{ $etiquetasCategoria[$evaluacion->categoria] }}
                        </p>
                        <p class="text-sm text-gg-tinta-suave mt-2">
                            Evaluado el {{ $evaluacion->evaluado_at->format('d/m/Y') }}
                            · {{ $totalCompletados }} {{ $totalCompletados === 1 ? 'encuesta completada' : 'encuestas completadas' }}
                        </p>

                        {{-- Mini pista: probabilidad de cada nivel --}}
                        @php $probs = [(float) $evaluacion->prob_0, (float) $evaluacion->prob_1, (float) $evaluacion->prob_2]; @endphp
                        <div class="mt-6 max-w-md" aria-hidden="true">
                            <div class="flex h-2.5 rounded-full overflow-hidden gap-0.5 bg-gg-papel-hondo">
                                @foreach($probs as $i => $p)
                                    <div class="h-full first:rounded-l-full last:rounded-r-full"
                                         style="width: {{ max(round($p * 100, 1), 1) }}%; background-color: {{ $bgHex[$i] }}; opacity: {{ $i === $evaluacion->categoria ? 1 : .35 }}"></div>
                                @endforeach
                            </div>
                            <div class="mt-2 flex justify-between text-xs text-gg-tinta-suave font-mono">
                                @foreach(['Bajo', 'Medio', 'Alto'] as $i => $nivel)
                                    <span class="{{ $i === $evaluacion->categoria ? 'text-gg-tinta' : '' }}">{{ $nivel }} {{ round($probs[$i] * 100) }}&thinsp;%</span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-base text-gg-tinta-suave mt-1">Aún no se ha calculado.</p>
                    @endif

                    <div class="mt-7 flex flex-wrap gap-2">
                        <x-boton variante="primario" href="{{ route('resultado.show', $ultimoDiligenciamiento) }}" icono-final="flecha">
                            Ver resultado
                        </x-boton>
                        <x-boton variante="secundario" href="{{ route('seguimiento.show') }}" icono="seguimiento">
                            Seguimiento
                        </x-boton>
                    </div>
                </div>
            </section>

            {{-- Columna de acciones --}}
            <div class="grid gap-5 content-start">
                {{-- Nueva encuesta --}}
                <section class="gg-marca rounded-bloque overflow-hidden p-6 sm:p-7 shadow-elev-2">
                    <x-icono nombre="encuesta" class="w-7 h-7 text-gg-acento" />
                    <p class="mt-4 font-display text-xl font-medium text-white">¿Listo para una nueva encuesta?</p>
                    <p class="text-sm text-white/75 mt-1.5 leading-relaxed">
                        Vuelve a diligenciar el instrumento para ver tu evolución.
                    </p>
                    <div class="mt-5">
                        <x-boton variante="claro" href="{{ route('encuesta.iniciar') }}" icono-final="flecha">
                            Comenzar
                        </x-boton>
                    </div>
                </section>

                {{-- Invitación al SUS de la prueba piloto (Sprint 6) --}}
                @unless(Auth::user()->evaluacionUsabilidad()->exists())
                <a href="{{ route('usabilidad.show') }}" class="block group rounded-tarjeta">
                    <x-tarjeta interactiva class="flex items-center gap-4">
                        <span class="shrink-0 w-11 h-11 rounded-control bg-gg-primario-suave text-gg-primario inline-flex items-center justify-center">
                            <x-icono nombre="usabilidad" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-medium text-gg-tinta">¿Cómo te pareció usar GutGuardián?</p>
                            <p class="text-sm text-gg-tinta-suave mt-0.5">
                                Diez afirmaciones, unos dos minutos. Nos ayuda a validar el aplicativo.
                            </p>
                        </div>
                        <span class="hidden sm:inline-flex shrink-0 text-sm font-medium text-gg-primario items-center gap-1">
                            Evaluar
                            <x-icono nombre="flecha" class="w-4 h-4 transition-transform duration-200 group-hover:translate-x-0.5" />
                        </span>
                    </x-tarjeta>
                </a>
                @endunless
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <nav aria-label="Accesos rápidos" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            @foreach([
                ['ruta' => 'historial.show',   'icono' => 'historial',   'titulo' => 'Historial',   'texto' => 'Tus encuestas, de la más reciente a la más antigua'],
                ['ruta' => 'seguimiento.show', 'icono' => 'seguimiento', 'titulo' => 'Seguimiento', 'texto' => 'Evolución de tu riesgo y tus síntomas'],
                ['ruta' => 'alertas.index',    'icono' => 'alertas',     'titulo' => 'Alertas',     'texto' => 'Avisos cuando tu nivel cambia'],
                ['ruta' => 'mis-datos.show',   'icono' => 'datos',       'titulo' => 'Mis datos',   'texto' => 'Tu información y quién la consultó'],
            ] as $acceso)
            <a href="{{ route($acceso['ruta']) }}" class="block rounded-tarjeta">
                <x-tarjeta interactiva padding="p-4 sm:p-5" class="h-full">
                    <span class="w-10 h-10 rounded-control bg-gg-papel border border-gg-borde text-gg-primario inline-flex items-center justify-center">
                        <x-icono :nombre="$acceso['icono']" />
                    </span>
                    <p class="mt-3 text-base font-medium text-gg-tinta">{{ $acceso['titulo'] }}</p>
                    <p class="hidden sm:block text-sm text-gg-tinta-suave mt-0.5 leading-snug">{{ $acceso['texto'] }}</p>
                </x-tarjeta>
            </a>
            @endforeach
        </nav>
    @endif

    {{-- Aviso obligatorio Res. 3100 de 2019 --}}
    <div>
        <x-aviso-no-diagnostico />
    </div>

    </div>

</x-layouts.estudiante>
