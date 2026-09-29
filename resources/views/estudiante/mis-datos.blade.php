@php
    $acciones = [
        'consulta_ficha' => 'Consultó tu ficha',
        'consulta_resultado' => 'Consultó uno de tus resultados',
    ];
    $consentimiento = $user->consentimientos->sortByDesc('aceptado_at')->first();
@endphp

<x-layouts.estudiante titulo="Mis datos">

    <div class="mb-6">
        <h1 class="font-display text-2xl font-medium text-gg-tinta">Mis datos</h1>
        <p class="text-sm text-gg-tinta-suave mt-1">
            Qué información guarda GutGuardián sobre ti, quién la ha consultado y cómo ejercer tus derechos
            (Ley 1581 de 2012).
        </p>
    </div>

    <x-tarjeta class="mb-4">
        <h2 class="text-sm font-medium text-gg-tinta mb-3">Información registrada</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-2xs text-gg-tinta-suave">Nombre</dt><dd class="text-gg-tinta">{{ $user->name }}</dd></div>
            <div><dt class="text-2xs text-gg-tinta-suave">Correo</dt><dd class="text-gg-tinta break-all">{{ $user->email }}</dd></div>
            <div><dt class="text-2xs text-gg-tinta-suave">Código de participante</dt><dd class="font-mono text-gg-tinta">{{ $user->codigo_participante }}</dd></div>
            <div><dt class="text-2xs text-gg-tinta-suave">Programa y semestre</dt>
                <dd class="text-gg-tinta">{{ $user->perfil?->programa?->nombre ?? '—' }}{{ $user->perfil?->semestre ? ' · semestre '.$user->perfil->semestre : '' }}</dd></div>
            <div><dt class="text-2xs text-gg-tinta-suave">Encuestas completadas</dt><dd class="font-mono text-gg-tinta">{{ $totalEncuestas }}</dd></div>
            <div><dt class="text-2xs text-gg-tinta-suave">Consentimiento informado</dt>
                <dd class="text-gg-tinta">
                    @if($consentimiento)
                        Versión {{ $consentimiento->version_politica }} · aceptado el {{ $consentimiento->aceptado_at->format('d/m/Y H:i') }}
                    @else — @endif
                </dd></div>
        </dl>
        <div class="flex flex-wrap gap-2 mt-5">
            <x-boton tamano="sm" href="{{ route('mis-datos.descargar') }}">Descargar copia (PDF)</x-boton>
            <x-boton variante="secundario" tamano="sm" href="{{ route('perfil.edit') }}">Actualizar mis datos</x-boton>
        </div>
    </x-tarjeta>

    <x-tarjeta class="mb-4">
        <h2 class="text-sm font-medium text-gg-tinta mb-1">Quién ha consultado tu información</h2>
        <p class="text-2xs text-gg-tinta-suave mb-3">
            Solo el personal de salud autorizado puede ver tus resultados. Cada consulta queda registrada.
        </p>
        @if($accesos->isEmpty())
            <p class="text-sm text-gg-tinta-suave">Nadie más ha consultado tu información clínica.</p>
        @else
            <ul class="divide-y divide-gg-borde text-sm">
                @foreach($accesos as $acceso)
                <li class="py-2 flex flex-wrap justify-between gap-x-4">
                    <span class="text-gg-tinta">{{ $acceso->causer?->name ?? 'Usuario eliminado' }} — {{ $acciones[$acceso->event] ?? $acceso->description }}</span>
                    <span class="font-mono text-xs text-gg-tinta-suave">{{ $acceso->created_at->format('d/m/Y H:i') }}</span>
                </li>
                @endforeach
            </ul>
        @endif
    </x-tarjeta>

    <x-tarjeta>
        <h2 class="text-sm font-medium text-gg-tinta mb-2">Tus derechos</h2>
        <ul class="list-disc list-inside space-y-1 text-sm text-gg-tinta-suave">
            <li><span class="text-gg-tinta">Conocer</span> tu información: está en esta página y en la copia descargable.</li>
            <li><span class="text-gg-tinta">Actualizar y rectificar</span> tus datos sociodemográficos desde «Perfil».
                Tus encuestas completadas no se modifican, para conservar la trazabilidad del estudio.</li>
            <li><span class="text-gg-tinta">Saber qué uso</span> se ha dado a tu información: consulta el registro de accesos de arriba.</li>
            <li><span class="text-gg-tinta">Revocar la autorización o retirarte</span> del estudio: comunícate con el equipo responsable
                (Programas de Ingeniería de Sistemas, Enfermería y Nutrición y Dietética, Universidad Mariana). La solicitud se atiende
                conforme a la Ley 1581 de 2012 y a los tiempos de conservación de la Resolución 1995 de 1999.</li>
        </ul>
    </x-tarjeta>

</x-layouts.estudiante>
