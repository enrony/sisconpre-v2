<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 18px 24px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 15px; margin: 0 0 2px; color: #0073c3; }
        .subtitulo { font-size: 9px; color: #6b7280; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
        th { background: #0073c3; color: #fff; font-weight: 600; }
        tr:nth-child(even) td { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    <p class="subtitulo">Generado el {{ now()->format('Y-m-d H:i') }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($columnas as $etiqueta)
                    <th>{{ $etiqueta }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach (array_keys($columnas) as $clave)
                        <td>{{ $fila[$clave] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columnas) }}">Sin datos para los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
