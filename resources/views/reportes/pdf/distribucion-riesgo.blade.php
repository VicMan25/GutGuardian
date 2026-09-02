<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de riesgo institucional</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #16302B; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.subtitulo { color: #5A6560; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #E0E3DC; }
        th { text-transform: uppercase; font-size: 9px; color: #5A6560; }
        .resumen td { font-weight: bold; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; }
    </style>
</head>
<body>
    <h1>Reporte de riesgo institucional — GutGuardián</h1>
    <p class="subtitulo">Generado el {{ now()->format('d/m/Y H:i') }} — {{ $total }} evaluaciones</p>

    <table class="resumen">
        <thead>
            <tr><th>Nivel de riesgo</th><th>Total</th><th>Porcentaje</th></tr>
        </thead>
        <tbody>
            @foreach($resumen as $fila)
            <tr>
                <td>{{ $fila['etiqueta'] }}</td>
                <td>{{ $fila['total'] }}</td>
                <td>{{ $fila['porcentaje'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr><th>Estudiante</th><th>Código</th><th>Programa</th><th>Riesgo</th><th>Fecha</th></tr>
        </thead>
        <tbody>
            @foreach($detalle as $fila)
            <tr>
                <td>{{ $fila['estudiante'] }}</td>
                <td>{{ $fila['codigo_participante'] }}</td>
                <td>{{ $fila['programa'] }}</td>
                <td>{{ $fila['categoria'] }}</td>
                <td>{{ $fila['fecha'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="subtitulo">GutGuardián no emite diagnósticos médicos. Es una herramienta de autocuidado y tamizaje — Resolución 3100 de 2019.</p>
</body>
</html>
