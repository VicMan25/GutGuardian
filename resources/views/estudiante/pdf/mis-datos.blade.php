@php
    $categorias = ['Riesgo bajo', 'Riesgo medio', 'Riesgo alto'];
    $pct = fn ($p) => number_format($p * 100, 1, ',', '.').' %';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Copia de mis datos — GutGuardián</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #16302B; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        h2 { font-size: 12px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #E0E3DC; }
        h3 { font-size: 11px; margin: 12px 0 4px; }
        p.suave { color: #5A6560; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { text-align: left; padding: 4px 6px; border-bottom: 1px solid #E0E3DC; vertical-align: top; }
        th { font-size: 8px; text-transform: uppercase; color: #5A6560; }
        .aviso { border: 1px solid #C08A2E; padding: 6px 8px; margin-top: 16px; }
    </style>
</head>
<body>
    <h1>Copia de mis datos — GutGuardián</h1>
    <p class="suave">Generada el {{ now()->format('d/m/Y H:i') }} a solicitud del titular (Ley 1581 de 2012).</p>

    <h2>Datos de la cuenta</h2>
    <table>
        <tr><th>Nombre</th><td>{{ $user->name }}</td><th>Correo</th><td>{{ $user->email }}</td></tr>
        <tr><th>Código</th><td>{{ $user->codigo_participante }}</td><th>Estado</th><td>{{ $user->activo ? 'Activa' : 'Desactivada' }}</td></tr>
        <tr><th>Género</th><td>{{ $user->perfil?->genero ?? '—' }}</td><th>Edad</th><td>{{ $user->perfil?->edad ?? '—' }}</td></tr>
        <tr><th>Programa</th><td>{{ $user->perfil?->programa?->nombre ?? '—' }}</td><th>Semestre</th><td>{{ $user->perfil?->semestre ?? '—' }}</td></tr>
    </table>

    <h2>Consentimiento informado</h2>
    <table>
        <thead><tr><th>Versión</th><th>Fecha de aceptación</th><th>Dirección IP</th></tr></thead>
        <tbody>
            @forelse($user->consentimientos as $c)
                <tr><td>{{ $c->version_politica }}</td><td>{{ $c->aceptado_at->format('d/m/Y H:i') }}</td><td>{{ $c->ip }}</td></tr>
            @empty
                <tr><td colspan="3">Sin registro.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Encuestas completadas</h2>
    @forelse($user->diligenciamientos as $d)
        @php $evaluacion = $d->evaluaciones->sortByDesc('evaluado_at')->first(); @endphp
        <h3>Encuesta del {{ $d->completado_at?->format('d/m/Y') }}</h3>
        @if($evaluacion)
            <p>
                Clasificación: <strong>{{ $categorias[$evaluacion->categoria] }}</strong>
                (bajo {{ $pct($evaluacion->prob_0) }} · medio {{ $pct($evaluacion->prob_1) }} · alto {{ $pct($evaluacion->prob_2) }})
                — modelo v{{ $evaluacion->versionModelo?->version }}, evaluado el {{ $evaluacion->evaluado_at->format('d/m/Y') }}.
            </p>
        @endif
        <table>
            <thead><tr><th style="width:8%">Pregunta</th><th style="width:52%">Enunciado</th><th>Respuesta</th></tr></thead>
            <tbody>
                @foreach($d->respuestas as $r)
                    <tr>
                        <td>{{ $r->pregunta?->codigo }}</td>
                        <td>{{ $r->pregunta?->enunciado }}</td>
                        <td>{{ $r->item ? $r->item->etiqueta.': ' : '' }}{{ $r->opcion?->etiqueta ?? $r->valor_texto ?? $r->valor_numerico }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>No hay encuestas completadas.</p>
    @endforelse

    <h2>Consultas de terceros a tu información clínica</h2>
    <table>
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th></tr></thead>
        <tbody>
            @forelse($accesos as $a)
                <tr><td>{{ $a->created_at->format('d/m/Y H:i') }}</td><td>{{ $a->causer?->name ?? '—' }}</td><td>{{ $a->description }}</td></tr>
            @empty
                <tr><td colspan="3">Ninguna.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="aviso">
        GutGuardián no realiza diagnóstico clínico y no sustituye la consulta con un profesional de salud.
        La clasificación de riesgo es una orientación de autocuidado (Resolución 3100 de 2019).
    </p>
</body>
</html>
